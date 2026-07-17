<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashTransactionController extends Controller
{
    /**
     * Menampilkan seluruh transaksi buku kas.
     */
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->input('search')
        );

        $direction = $request->input('direction');
        $category = $request->input('category');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $transactions = CashTransaction::query()
            ->with([
                'user:id,name',
            ])
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
                    $direction,
                    [
                        'income',
                        'expense',
                    ],
                    true
                ),
                fn ($query) => $query->where(
                    'direction',
                    $direction
                )
            )
            ->when(
                filled($category),
                fn ($query) => $query->where(
                    'category',
                    $category
                )
            )
            ->when(
                filled($dateFrom),
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '>=',
                    $dateFrom
                )
            )
            ->when(
                filled($dateTo),
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

        /*
         * Statistik seluruh buku kas.
         */
        $totalIncome = round(
            (float) CashTransaction::query()
                ->income()
                ->sum('amount'),
            2
        );

        $totalExpense = round(
            (float) CashTransaction::query()
                ->expense()
                ->sum('amount'),
            2
        );

        $todayBalance = round(
            (float) CashTransaction::query()
                ->whereDate(
                    'transaction_date',
                    today()
                )
                ->selectRaw(
                    '
                    COALESCE(
                        SUM(
                            CASE
                                WHEN direction = "income"
                                    THEN amount
                                ELSE -amount
                            END
                        ),
                        0
                    ) AS balance
                    '
                )
                ->value('balance'),
            2
        );

        $statistics = [
            'income' => $totalIncome,

            'expense' => $totalExpense,

            'balance' => round(
                $totalIncome - $totalExpense,
                2
            ),

            'today' => $todayBalance,
        ];

        /*
         * Kategori diambil dari transaksi yang sudah ada
         * untuk kebutuhan filter.
         */
        $categories = CashTransaction::query()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view(
            'cash-transactions.index',
            compact(
                'transactions',
                'statistics',
                'categories',
                'search',
                'direction',
                'category',
                'dateFrom',
                'dateTo'
            )
        );
    }

    /**
     * Menampilkan form transaksi kas manual.
     *
     * Form ini diperuntukkan bagi kas masuk manual.
     * Kas keluar akan dialihkan ke menu Pengeluaran.
     */
    public function create(): View
    {
        return view(
            'cash-transactions.create'
        );
    }

    /**
     * Menyimpan transaksi kas manual.
     */
    public function store(
        Request $request
    ): RedirectResponse {
        $data = $request->validate([
            'transaction_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'direction' => [
                'required',

                Rule::in([
                    'income',
                    'expense',
                ]),
            ],

            'category' => [
                'required',
                'string',
                'max:150',
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
                'Tanggal transaksi wajib diisi.',

            'transaction_date.date' =>
                'Tanggal transaksi tidak valid.',

            'transaction_date.before_or_equal' =>
                'Tanggal transaksi tidak boleh melebihi hari ini.',

            'direction.required' =>
                'Jenis transaksi wajib dipilih.',

            'direction.in' =>
                'Jenis transaksi tidak valid.',

            'category.required' =>
                'Kategori transaksi wajib diisi.',

            'category.string' =>
                'Kategori transaksi harus berupa teks.',

            'category.max' =>
                'Kategori transaksi maksimal 150 karakter.',

            'amount.required' =>
                'Nominal transaksi wajib diisi.',

            'amount.numeric' =>
                'Nominal transaksi harus berupa angka.',

            'amount.min' =>
                'Nominal transaksi minimal Rp1.',

            'payment_method.required' =>
                'Metode pembayaran wajib dipilih.',

            'payment_method.in' =>
                'Metode pembayaran tidak valid.',

            'description.required' =>
                'Keterangan transaksi wajib diisi.',

            'description.string' =>
                'Keterangan transaksi harus berupa teks.',

            'description.max' =>
                'Keterangan transaksi maksimal 2.000 karakter.',
        ]);

        /*
         * Kas keluar wajib melalui menu Pengeluaran
         * agar kas dan jurnal akuntansi dibuat bersama.
         */
        if ($data['direction'] === 'expense') {
            return redirect()
                ->route('expenses.create')
                ->withInput([
                    'transaction_date' =>
                        $data['transaction_date'],

                    'category' =>
                        $data['category'],

                    'amount' =>
                        $data['amount'],

                    'payment_method' =>
                        $data['payment_method'],

                    'description' =>
                        $data['description'],
                ])
                ->with(
                    'warning',
                    'Kas keluar harus dicatat melalui menu Pengeluaran agar jurnal otomatis ikut dibuat.'
                );
        }

        /*
         * Transaksi manual dari Buku Kas hanya
         * digunakan untuk kas masuk.
         */
        $transaction = CashTransaction::create([
            'transaction_date' =>
                $data['transaction_date'],

            'direction' =>
                'income',

            'category' =>
                trim($data['category']),

            'amount' =>
                round(
                    (float) $data['amount'],
                    2
                ),

            'payment_method' =>
                $data['payment_method'],

            'description' =>
                trim($data['description']),

            /*
             * Transaksi manual tidak terhubung
             * ke model sumber lainnya.
             */
            'source_type' =>
                null,

            'source_id' =>
                null,

            'user_id' =>
                auth()->id(),
        ]);

        return redirect()
            ->route('cash-transactions.index')
            ->with(
                'success',
                'Transaksi kas masuk '
                . $transaction->transaction_code
                . ' berhasil ditambahkan.'
            );
    }
}
