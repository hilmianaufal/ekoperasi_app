<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\LoanInstallment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InstallmentController extends Controller
{
    public function index(Request $request): View
    {
        $this->refreshOverdueStatuses();

        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $installments = LoanInstallment::query()
            ->with([
                'loan:id,loan_number,member_id,status',
                'loan.member:id,member_number,name,photo',
            ])
            ->whereHas('loan', function ($query) {
                $query->whereIn('status', [
                    'active',
                    'paid',
                ]);
            })
            ->when($search, function ($query) use ($search) {
                $query->whereHas('loan', function ($loanQuery) use ($search) {
                    $loanQuery
                        ->where('loan_number', 'like', "%{$search}%")
                        ->orWhereHas(
                            'member',
                            function ($memberQuery) use ($search) {
                                $memberQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere(
                                        'member_number',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                });
            })
            ->when(
                in_array($status, [
                    'unpaid',
                    'partial',
                    'paid',
                    'overdue',
                ], true),
                fn($query) => $query->where('status', $status)
            )
            ->when(
                $dateFrom,
                fn($query) => $query->whereDate(
                    'due_date',
                    '>=',
                    $dateFrom
                )
            )
            ->when(
                $dateTo,
                fn($query) => $query->whereDate(
                    'due_date',
                    '<=',
                    $dateTo
                )
            )
            ->orderByRaw("
                CASE
                    WHEN status = 'overdue' THEN 1
                    WHEN status = 'unpaid' THEN 2
                    WHEN status = 'partial' THEN 3
                    ELSE 4
                END
            ")
            ->orderBy('due_date')
            ->paginate(15)
            ->withQueryString();

        $scheduledOutstanding = LoanInstallment::query()
            ->whereHas('loan', function ($query) {
                $query
                    ->where('status', 'active')
                    ->where('is_legacy', false);
            })
            ->selectRaw(
                'COALESCE(SUM(total_amount - paid_amount), 0) AS total'
            )
            ->value('total');

        $legacyOutstanding = Loan::query()
            ->where('status', 'active')
            ->where('is_legacy', true)
            ->sum('outstanding_principal');

        $outstanding = round(
            (float) $scheduledOutstanding
                + (float) $legacyOutstanding,
            2
        );

        $statistics = [
            'outstanding' => (float) $outstanding,

            'overdue' => LoanInstallment::query()
                ->where('status', 'overdue')
                ->whereHas('loan', function ($query) {
                    $query->where('status', 'active');
                })
                ->count(),

            'due_this_month' => LoanInstallment::query()
                ->whereHas('loan', function ($query) {
                    $query->where('status', 'active');
                })
                ->whereIn('status', [
                    'unpaid',
                    'partial',
                    'overdue',
                ])
                ->whereYear('due_date', now()->year)
                ->whereMonth('due_date', now()->month)
                ->count(),

            'paid_this_month' => InstallmentPayment::query()
                ->whereYear('payment_date', now()->year)
                ->whereMonth('payment_date', now()->month)
                ->sum('amount'),
        ];

        $recentPayments = InstallmentPayment::query()
            ->with([
                'installment:id,loan_id,installment_number',
                'installment.loan:id,loan_number,member_id',
                'installment.loan.member:id,member_number,name',
                'user:id,name',
            ])
            ->latest('payment_date')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('installments.index', compact(
            'installments',
            'statistics',
            'recentPayments',
            'search',
            'status',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create(
        LoanInstallment $loanInstallment
    ): View|RedirectResponse {
        $this->refreshOverdueStatuses();

        $loanInstallment->load([
            'loan.member',
            'payments.user',
        ]);

        if ($loanInstallment->loan->status !== 'active') {
            return redirect()
                ->route('loans.show', $loanInstallment->loan)
                ->with(
                    'error',
                    'Pembayaran hanya dapat dilakukan untuk pinjaman aktif.'
                );
        }

        if ($loanInstallment->status === 'paid') {
            return redirect()
                ->route('loans.show', $loanInstallment->loan)
                ->with(
                    'error',
                    'Angsuran ini sudah lunas.'
                );
        }

        return view('installments.pay', compact(
            'loanInstallment'
        ));
    }

    public function store(
        Request $request,
        LoanInstallment $loanInstallment
    ): RedirectResponse {
        $data = $request->validate([
            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
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
            'reference_number' => [
                'nullable',
                'required_if:payment_method,transfer',
                'string',
                'max:150',
            ],
            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'payment_date.required' => 'Tanggal pembayaran wajib diisi.',
            'payment_date.before_or_equal' => 'Tanggal pembayaran tidak boleh melebihi hari ini.',
            'amount.required' => 'Nominal pembayaran wajib diisi.',
            'amount.numeric' => 'Nominal pembayaran harus berupa angka.',
            'amount.min' => 'Nominal pembayaran minimal Rp1.',
            'payment_method.required' => 'Metode pembayaran wajib dipilih.',
            'reference_number.required_if' => 'Nomor referensi transfer wajib diisi.',
        ]);

        $payment = DB::transaction(
            function () use (
                $loanInstallment,
                $data
            ): InstallmentPayment {
                $installment = LoanInstallment::query()
                    ->with('loan')
                    ->lockForUpdate()
                    ->findOrFail($loanInstallment->id);

                if ($installment->loan->status !== 'active') {
                    throw ValidationException::withMessages([
                        'amount' => 'Pinjaman sudah tidak aktif.',
                    ]);
                }

                $remainingAmount = max(
                    (float) $installment->total_amount
                        - (float) $installment->paid_amount,
                    0
                );

                if ($remainingAmount <= 0) {
                    throw ValidationException::withMessages([
                        'amount' => 'Angsuran ini sudah lunas.',
                    ]);
                }

                $paymentAmount = round(
                    (float) $data['amount'],
                    2
                );

                if ($paymentAmount > $remainingAmount) {
                    throw ValidationException::withMessages([
                        'amount' => 'Pembayaran melebihi sisa tagihan. Sisa tagihan Rp'
                            . number_format(
                                $remainingAmount,
                                0,
                                ',',
                                '.'
                            )
                            . '.',
                    ]);
                }

                $newPaidAmount = round(
                    (float) $installment->paid_amount
                        + $paymentAmount,
                    2
                );

                $remainingAfter = max(
                    round(
                        (float) $installment->total_amount
                            - $newPaidAmount,
                        2
                    ),
                    0
                );

                $payment = InstallmentPayment::create([
                    'loan_installment_id' => $installment->id,
                    'user_id' => auth()->id(),
                    'payment_date' => $data['payment_date'],
                    'amount' => $paymentAmount,
                    'remaining_after' => $remainingAfter,
                    'payment_method' => $data['payment_method'],
                    'reference_number' => $data['reference_number'] ?? null,
                    'notes' => $data['notes'] ?? null,
                ]);

                $payment->update([
                    'payment_code' => sprintf(
                        'ANG-%s-%06d',
                        now()->format('Ymd'),
                        $payment->id
                    ),
                ]);

                if ($remainingAfter <= 0) {
                    $installmentStatus = 'paid';
                } elseif ($installment->due_date->isPast()) {
                    $installmentStatus = 'overdue';
                } else {
                    $installmentStatus = 'partial';
                }

                $installment->update([
                    'paid_amount' => $newPaidAmount,
                    'paid_at' => $remainingAfter <= 0
                        ? now()
                        : null,
                    'status' => $installmentStatus,
                ]);

                $hasUnpaidInstallments = $installment
                    ->loan
                    ->installments()
                    ->where('status', '!=', 'paid')
                    ->exists();

                if (!$hasUnpaidInstallments) {
                    $installment->loan->update([
                        'status' => 'paid',
                    ]);
                }

                return $payment;
            }
        );

        return redirect()
            ->route('installment-payments.show', $payment)
            ->with(
                'success',
                'Pembayaran angsuran berhasil disimpan.'
            );
    }

    private function refreshOverdueStatuses(): void
    {
        LoanInstallment::query()
            ->whereIn('status', [
                'unpaid',
                'partial',
            ])
            ->whereDate('due_date', '<', today())
            ->whereHas('loan', function ($query) {
                $query->where('status', 'active');
            })
            ->update([
                'status' => 'overdue',
            ]);
    }
}
