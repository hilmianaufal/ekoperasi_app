<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Services\Accounting\AccountingJournalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    /**
     * Kategori pengeluaran operasional koperasi.
     */
    private const CATEGORIES = [
        'Gaji Karyawan',
        'Listrik',
        'Air',
        'Internet',
        'Iuran',
        'ATK',
        'Transportasi',
        'Konsumsi',
        'Pemeliharaan',
        'Lainnya',
    ];

    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->input('search')
        );

        $category = $request->input('category');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $expenses = CashTransaction::query()
            ->with([
                'user:id,name',
            ])
            ->where(
                'direction',
                'expense'
            )
            ->where(
                'source_type',
                'manual_expense'
            )
            ->when(
                $search !== '',
                function ($query) use ($search): void {
                    $query->where(
                        function ($subQuery) use ($search): void {
                            $subQuery
                                ->where(
                                    'transaction_code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'category',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                in_array(
                    $category,
                    self::CATEGORIES,
                    true
                ),
                fn ($query) => $query->where(
                    'category',
                    $category
                )
            )
            ->when(
                $dateFrom,
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '>=',
                    $dateFrom
                )
            )
            ->when(
                $dateTo,
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '<=',
                    $dateTo
                )
            )
            ->latest('transaction_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        $baseQuery = CashTransaction::query()
            ->where(
                'direction',
                'expense'
            )
            ->where(
                'source_type',
                'manual_expense'
            );

        $statistics = [
            'total' => round(
                (float) (clone $baseQuery)
                    ->sum('amount'),
                2
            ),

            'this_month' => round(
                (float) (clone $baseQuery)
                    ->whereYear(
                        'transaction_date',
                        now()->year
                    )
                    ->whereMonth(
                        'transaction_date',
                        now()->month
                    )
                    ->sum('amount'),
                2
            ),

            'today' => round(
                (float) (clone $baseQuery)
                    ->whereDate(
                        'transaction_date',
                        today()
                    )
                    ->sum('amount'),
                2
            ),

            'transaction_count' => (clone $baseQuery)
                ->count(),
        ];

        $categories = self::CATEGORIES;

        return view(
            'expenses.index',
            compact(
                'expenses',
                'statistics',
                'categories',
                'search',
                'category',
                'dateFrom',
                'dateTo'
            )
        );
    }

    public function create(): View
    {
        $categories = self::CATEGORIES;

        return view(
            'expenses.create',
            compact('categories')
        );
    }

    public function store(
        Request $request,
        AccountingJournalService $journalService
    ): RedirectResponse {
        $data = $request->validate([
            'transaction_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'category' => [
                'required',
                Rule::in(self::CATEGORIES),
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],

            'payment_method' => [
                'required',

                Rule::in([
                    'cash',
                    'transfer',
                    'other',
                ]),
            ],

            'description' => [
                'required',
                'string',
                'max:2000',
            ],
        ], [
            'transaction_date.required' =>
                'Tanggal pengeluaran wajib diisi.',

            'transaction_date.date' =>
                'Tanggal pengeluaran tidak valid.',

            'transaction_date.before_or_equal' =>
                'Tanggal pengeluaran tidak boleh melebihi hari ini.',

            'category.required' =>
                'Kategori pengeluaran wajib dipilih.',

            'category.in' =>
                'Kategori pengeluaran tidak valid.',

            'amount.required' =>
                'Nominal pengeluaran wajib diisi.',

            'amount.numeric' =>
                'Nominal pengeluaran harus berupa angka.',

            'amount.min' =>
                'Nominal pengeluaran minimal Rp1.',

            'payment_method.required' =>
                'Metode pembayaran wajib dipilih.',

            'payment_method.in' =>
                'Metode pembayaran tidak valid.',

            'description.required' =>
                'Keterangan pengeluaran wajib diisi.',

            'description.max' =>
                'Keterangan maksimal 2.000 karakter.',
        ]);

        $expense = DB::transaction(
            function () use (
                $data,
                $journalService
            ): CashTransaction {
                $expense = CashTransaction::create([
                    'transaction_date' =>
                        $data['transaction_date'],

                    'direction' =>
                        'expense',

                    'category' =>
                        $data['category'],

                    'amount' =>
                        round(
                            (float) $data['amount'],
                            2
                        ),

                    'payment_method' =>
                        $data['payment_method'],

                    'description' =>
                        trim($data['description']),

                    'source_type' =>
                        'manual_expense',

                    /*
                     * Diisi setelah transaksi memiliki ID.
                     */
                    'source_id' =>
                        null,

                    'user_id' =>
                        auth()->id(),
                ]);

                /*
                 * Menghubungkan transaksi kas dengan
                 * sumber pengeluaran secara unik.
                 */
                $expense->updateQuietly([
                    'source_id' =>
                        $expense->id,
                ]);

                $expense->refresh();

                /*
                 * Otomatis membuat jurnal:
                 *
                 * Debit  Beban
                 * Kredit Kas atau Bank
                 */
                $journalService
                    ->recordManualExpense(
                        $expense
                    );

                return $expense;
            }
        );

        return redirect()
            ->route('expenses.index')
            ->with(
                'success',
                'Pengeluaran '
                . $expense->transaction_code
                . ' berhasil dicatat ke kas dan jurnal.'
            );
    }
}
