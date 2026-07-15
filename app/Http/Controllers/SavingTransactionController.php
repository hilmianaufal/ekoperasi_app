<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\SavingTransaction;
use App\Models\SavingType;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SavingTransactionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $savingTypeId = $request->input('saving_type_id');
        $transactionType = $request->input('transaction_type');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $transactions = SavingTransaction::query()
            ->with([
                'member:id,member_number,name,photo',
                'savingType:id,name,code',
                'user:id,name',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('transaction_code', 'like', "%{$search}%")
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('member_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                $savingTypeId,
                fn ($query) => $query->where('saving_type_id', $savingTypeId)
            )
            ->when(
                in_array($transactionType, ['deposit', 'withdrawal'], true),
                fn ($query) => $query->where(
                    'transaction_type',
                    $transactionType
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

        $totals = SavingTransaction::query()
            ->selectRaw("
                COALESCE(
                    SUM(
                        CASE
                            WHEN transaction_type = 'deposit'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_deposits
            ")
            ->selectRaw("
                COALESCE(
                    SUM(
                        CASE
                            WHEN transaction_type = 'withdrawal'
                            THEN amount
                            ELSE 0
                        END
                    ),
                    0
                ) AS total_withdrawals
            ")
            ->first();

        $statistics = [
            'deposits' => (float) $totals->total_deposits,
            'withdrawals' => (float) $totals->total_withdrawals,
            'balance' => (float) $totals->total_deposits
                - (float) $totals->total_withdrawals,
            'transactions' => SavingTransaction::count(),
        ];

        $savingTypes = SavingType::query()
            ->orderBy('name')
            ->get();

        return view('savings.index', compact(
            'transactions',
            'statistics',
            'savingTypes',
            'search',
            'savingTypeId',
            'transactionType',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create(Request $request): View
    {
        $members = Member::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get([
                'id',
                'member_number',
                'name',
            ]);

        $savingTypes = SavingType::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $selectedMemberId = $request->integer('member_id') ?: null;
        $selectedSavingTypeId = $request->integer('saving_type_id') ?: null;

        return view('savings.create', compact(
            'members',
            'savingTypes',
            'selectedMemberId',
            'selectedSavingTypeId'
        ));
    }

    public function balance(Request $request): JsonResponse
    {
        $data = $request->validate([
            'member_id' => ['required', 'exists:members,id'],
            'saving_type_id' => ['required', 'exists:saving_types,id'],
        ]);

        $balance = $this->getBalance(
            (int) $data['member_id'],
            (int) $data['saving_type_id']
        );

        return response()->json([
            'balance' => $balance,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'member_id' => [
                'required',
                Rule::exists('members', 'id')->where(
                    fn ($query) => $query->where('status', 'active')
                ),
            ],
            'saving_type_id' => [
                'required',
                Rule::exists('saving_types', 'id')->where(
                    fn ($query) => $query->where('is_active', true)
                ),
            ],
            'transaction_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],
            'transaction_type' => [
                'required',
                Rule::in(['deposit', 'withdrawal']),
            ],
            'amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ], [
            'member_id.required' => 'Anggota wajib dipilih.',
            'member_id.exists' => 'Anggota tidak ditemukan atau tidak aktif.',
            'saving_type_id.required' => 'Jenis simpanan wajib dipilih.',
            'saving_type_id.exists' => 'Jenis simpanan tidak ditemukan atau tidak aktif.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi.',
            'transaction_date.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'transaction_type.required' => 'Jenis transaksi wajib dipilih.',
            'amount.required' => 'Nominal transaksi wajib diisi.',
            'amount.numeric' => 'Nominal transaksi harus berupa angka.',
            'amount.min' => 'Nominal transaksi minimal Rp1.',
        ]);

        DB::transaction(function () use ($data): void {
            $member = Member::query()
                ->lockForUpdate()
                ->findOrFail($data['member_id']);

            $savingType = SavingType::query()
                ->findOrFail($data['saving_type_id']);

            if ($member->status !== 'active') {
                throw ValidationException::withMessages([
                    'member_id' => 'Anggota yang dipilih sedang tidak aktif.',
                ]);
            }

            if (!$savingType->is_active) {
                throw ValidationException::withMessages([
                    'saving_type_id' => 'Jenis simpanan sedang tidak aktif.',
                ]);
            }

            $currentBalance = $this->getBalance(
                $member->id,
                $savingType->id
            );

            $amount = (float) $data['amount'];

            if ($data['transaction_type'] === 'withdrawal') {
                if (!$savingType->is_withdrawable) {
                    throw ValidationException::withMessages([
                        'transaction_type' => 'Jenis simpanan ini tidak dapat ditarik.',
                    ]);
                }

                if ($amount > $currentBalance) {
                    throw ValidationException::withMessages([
                        'amount' => 'Saldo tidak mencukupi. Saldo tersedia Rp'
                            . number_format($currentBalance, 0, ',', '.')
                            . '.',
                    ]);
                }

                $balanceAfter = $currentBalance - $amount;
            } else {
                $balanceAfter = $currentBalance + $amount;
            }

            $transaction = SavingTransaction::create([
                'member_id' => $member->id,
                'saving_type_id' => $savingType->id,
                'user_id' => auth()->id(),
                'transaction_date' => $data['transaction_date'],
                'transaction_type' => $data['transaction_type'],
                'amount' => $amount,
                'balance_after' => $balanceAfter,
                'notes' => $data['notes'] ?? null,
            ]);

            $transaction->update([
                'transaction_code' => sprintf(
                    'SMP-%s-%06d',
                    now()->format('Ymd'),
                    $transaction->id
                ),
            ]);
        });

        return redirect()
            ->route('savings.index')
            ->with('success', 'Transaksi simpanan berhasil disimpan.');
    }

    private function getBalance(
        int $memberId,
        int $savingTypeId
    ): float {
        $result = SavingTransaction::query()
            ->where('member_id', $memberId)
            ->where('saving_type_id', $savingTypeId)
            ->selectRaw("
                COALESCE(
                    SUM(
                        CASE
                            WHEN transaction_type = 'deposit'
                            THEN amount
                            ELSE -amount
                        END
                    ),
                    0
                ) AS balance
            ")
            ->first();

        return (float) $result->balance;
    }
}
