<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CashTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $direction = $request->input('direction');
        $category = $request->input('category');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $transactions = CashTransaction::query()
            ->with('user:id,name')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
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
                });
            })
            ->when(
                in_array($direction, [
                    'income',
                    'expense',
                ], true),
                fn ($query) => $query->where(
                    'direction',
                    $direction
                )
            )
            ->when(
                $category,
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

        $totalIncome = (float) CashTransaction::income()
            ->sum('amount');

        $totalExpense = (float) CashTransaction::expense()
            ->sum('amount');

        $statistics = [
            'income' => $totalIncome,
            'expense' => $totalExpense,
            'balance' => $totalIncome - $totalExpense,

            'today' => (float) CashTransaction::query()
                ->whereDate('transaction_date', today())
                ->selectRaw("
                    COALESCE(
                        SUM(
                            CASE
                                WHEN direction = 'income'
                                THEN amount
                                ELSE -amount
                            END
                        ),
                        0
                    ) AS balance
                ")
                ->value('balance'),
        ];

        $categories = CashTransaction::query()
            ->select('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('cash-transactions.index', compact(
            'transactions',
            'statistics',
            'categories',
            'search',
            'direction',
            'category',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create(): View
    {
        return view('cash-transactions.create');
    }

    public function store(Request $request): RedirectResponse
    {
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
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'direction.required' => 'Jenis transaksi wajib dipilih.',
            'category.required' => 'Kategori transaksi wajib diisi.',
            'amount.required' => 'Nominal transaksi wajib diisi.',
            'amount.numeric' => 'Nominal transaksi harus berupa angka.',
            'amount.min' => 'Nominal transaksi minimal Rp1.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'description.required' => 'Keterangan transaksi wajib diisi.',
        ]);

        CashTransaction::create([
            ...$data,
            'source_type' => null,
            'source_id' => null,
            'user_id' => auth()->id(),
        ]);

        return redirect()
            ->route('cash-transactions.index')
            ->with(
                'success',
                'Transaksi kas berhasil ditambahkan.'
            );
    }
}
