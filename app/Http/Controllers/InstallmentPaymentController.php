<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\InstallmentPayment;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Services\Accounting\AccountingJournalService;
use App\Services\CashLedgerService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class InstallmentPaymentController extends Controller
{
    public function show(
        InstallmentPayment $installmentPayment
    ): View {
        $installmentPayment->load([
            'installment.loan.member',
            'user:id,name',
        ]);

        $loan = $installmentPayment
            ->installment
            ->loan;

        $latestPaymentId = InstallmentPayment::query()
            ->whereHas(
                'installment',
                fn($query) => $query->where(
                    'loan_id',
                    $loan->id
                )
            )
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->value('id');

        $canEdit = (
            $installmentPayment->import_batch_id === null
            && (int) $latestPaymentId
            === (int) $installmentPayment->id
            && in_array(
                $loan->status,
                [
                    'active',
                    'paid',
                ],
                true
            )
        );

        return view(
            'installment-payments.show',
            compact(
                'installmentPayment',
                'canEdit'
            )
        );
    }

    public function edit(
        InstallmentPayment $installmentPayment
    ): View|RedirectResponse {
        $installmentPayment->load([
            'installment.loan.member',
            'installment.payments',
            'user:id,name',
        ]);

        if ($installmentPayment->import_batch_id !== null) {
            return redirect()
                ->route(
                    'installment-payments.show',
                    $installmentPayment
                )
                ->with(
                    'error',
                    'Pembayaran asli hasil import tidak dapat diedit.'
                );
        }

        if (!$this->isLatestLoanPayment($installmentPayment)) {
            return redirect()
                ->route(
                    'installment-payments.show',
                    $installmentPayment
                )
                ->with(
                    'error',
                    'Hanya pembayaran terbaru pada pinjaman yang dapat diedit.'
                );
        }

        $loan = $installmentPayment
            ->installment
            ->loan;

        if (!in_array($loan->status, [
            'active',
            'paid',
        ], true)) {
            return redirect()
                ->route(
                    'installment-payments.show',
                    $installmentPayment
                )
                ->with(
                    'error',
                    'Pembayaran pada pinjaman ini tidak dapat diedit.'
                );
        }

        $allocation = $this->resolveCurrentAllocation(
            $installmentPayment
        );

        $limits = $this->resolveComponentLimits(
            $installmentPayment
        );

        return view(
            'installment-payments.edit',
            compact(
                'installmentPayment',
                'loan',
                'allocation',
                'limits'
            )
        );
    }

    public function update(
        Request $request,
        InstallmentPayment $installmentPayment,
        CashLedgerService $cashLedgerService,
        AccountingJournalService $journalService
    ): RedirectResponse {
        $data = $request->validate([
            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'principal_amount' => [
                'required',
                'numeric',
                'min:0',
            ],

            'profit_share_amount' => [
                'nullable',
                'numeric',
                'min:0',
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
            'payment_date.required' =>
            'Tanggal pembayaran wajib diisi.',

            'payment_date.date' =>
            'Tanggal pembayaran tidak valid.',

            'payment_date.before_or_equal' =>
            'Tanggal pembayaran tidak boleh melebihi hari ini.',

            'principal_amount.required' =>
            'Angsuran pokok wajib diisi.',

            'principal_amount.numeric' =>
            'Angsuran pokok harus berupa angka.',

            'principal_amount.min' =>
            'Angsuran pokok tidak boleh negatif.',

            'profit_share_amount.numeric' =>
            'Bagi hasil harus berupa angka.',

            'profit_share_amount.min' =>
            'Bagi hasil tidak boleh negatif.',

            'payment_method.required' =>
            'Metode pembayaran wajib dipilih.',

            'payment_method.in' =>
            'Metode pembayaran tidak valid.',

            'reference_number.required_if' =>
            'Nomor referensi transfer wajib diisi.',

            'reference_number.max' =>
            'Nomor referensi maksimal 150 karakter.',

            'notes.max' =>
            'Catatan maksimal 1.000 karakter.',
        ]);

        $principal = round(
            (float) $data['principal_amount'],
            2
        );

        $profitShare = round(
            (float) (
                $data['profit_share_amount']
                ?? 0
            ),
            2
        );

        $newAmount = round(
            $principal + $profitShare,
            2
        );

        if ($newAmount <= 0) {
            throw ValidationException::withMessages([
                'principal_amount' =>
                'Total angsuran pokok dan bagi hasil harus lebih dari Rp0.',
            ]);
        }

        DB::transaction(
            function () use (
                $installmentPayment,
                $data,
                $principal,
                $profitShare,
                $newAmount,
                $cashLedgerService,
                $journalService
            ): void {
                $payment = InstallmentPayment::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $installmentPayment->id
                    );

                $payment->load([
                    'installment.loan.member',
                    'installment.payments',
                ]);

                if ($payment->import_batch_id !== null) {
                    throw ValidationException::withMessages([
                        'principal_amount' =>
                        'Pembayaran asli hasil import tidak dapat diedit.',
                    ]);
                }

                if (!$this->isLatestLoanPayment($payment)) {
                    throw ValidationException::withMessages([
                        'principal_amount' =>
                        'Hanya pembayaran terbaru pada pinjaman yang dapat diedit.',
                    ]);
                }

                $installment = LoanInstallment::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $payment->loan_installment_id
                    );

                $loan = Loan::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $installment->loan_id
                    );

                if (!in_array($loan->status, [
                    'active',
                    'paid',
                ], true)) {
                    throw ValidationException::withMessages([
                        'principal_amount' =>
                        'Pinjaman ini tidak dapat menerima perubahan pembayaran.',
                    ]);
                }

                $oldAllocation = $this
                    ->resolveCurrentAllocation(
                        $payment
                    );

                $paymentDate = Carbon::parse(
                    $data['payment_date']
                )->startOfDay();

                if ((bool) $loan->is_legacy) {
                    $this->updateLegacyPayment(
                        payment: $payment,
                        installment: $installment,
                        loan: $loan,
                        principal: $principal,
                        profitShare: $profitShare,
                        amount: $newAmount,
                        paymentDate: $paymentDate,
                        oldPrincipal: $oldAllocation['principal'],
                        oldProfitShare: $oldAllocation['profit_share']
                    );
                } else {
                    $this->updateScheduledPayment(
                        payment: $payment,
                        installment: $installment,
                        loan: $loan,
                        principal: $principal,
                        profitShare: $profitShare,
                        amount: $newAmount,
                        paymentDate: $paymentDate
                    );
                }

                $payment->update([
                    'payment_date' =>
                    $paymentDate->toDateString(),

                    'amount' =>
                    $newAmount,

                    'payment_method' =>
                    $data['payment_method'],

                    'reference_number' =>
                    $data['reference_number']
                        ?? null,

                    'notes' =>
                    filled($data['notes'] ?? null)
                        ? trim($data['notes'])
                        : null,

                    'principal_amount' =>
                    $principal,

                    'profit_share_amount' =>
                    $profitShare,

                    /*
                     * Administrasi tidak lagi menjadi
                     * bagian pembayaran angsuran.
                     */
                    'administration_fee' =>
                    0,
                ]);

                $payment->refresh()->load([
                    'installment.loan.member',
                ]);

                $this->synchronizeCashTransaction(
                    $payment,
                    $cashLedgerService
                );

                $this->rebuildJournal(
                    $payment,
                    $journalService
                );
            }
        );

        return redirect()
            ->route(
                'installment-payments.show',
                $installmentPayment
            )
            ->with(
                'success',
                'Pembayaran angsuran berhasil diperbarui. Kas, jurnal, dan saldo pinjaman telah dihitung ulang.'
            );
    }

    private function updateLegacyPayment(
        InstallmentPayment $payment,
        LoanInstallment $installment,
        Loan $loan,
        float $principal,
        float $profitShare,
        float $amount,
        Carbon $paymentDate,
        float $oldPrincipal,
        float $oldProfitShare
    ): void {
        /*
         * Kembalikan pokok pembayaran lama ke saldo,
         * lalu kurangi menggunakan pokok yang baru.
         */
        $restoredOutstanding = round(
            (float) $loan->outstanding_principal
                + $oldPrincipal,
            2
        );

        if ($principal > $restoredOutstanding) {
            throw ValidationException::withMessages([
                'principal_amount' =>
                'Angsuran pokok maksimal Rp'
                    . number_format(
                        $restoredOutstanding,
                        0,
                        ',',
                        '.'
                    )
                    . '.',
            ]);
        }

        $remainingPrincipal = max(
            round(
                $restoredOutstanding
                    - $principal,
                2
            ),
            0
        );

        $installment->update([
            'due_date' =>
            $paymentDate->toDateString(),

            'principal_amount' =>
            $principal,

            'interest_amount' =>
            $profitShare,

            'total_amount' =>
            $amount,

            'paid_amount' =>
            $amount,

            'paid_at' =>
            $paymentDate,

            'status' =>
            'paid',

            'reported_remaining_principal' =>
            $remainingPrincipal,
        ]);

        $payment->update([
            'remaining_after' =>
            $remainingPrincipal,
        ]);

        $loan->update([
            'outstanding_principal' =>
            $remainingPrincipal,

            'profit_share_paid' =>
            max(
                round(
                    (float) $loan->profit_share_paid
                        - $oldProfitShare
                        + $profitShare,
                    2
                ),
                0
            ),

            /*
             * Administrasi lama pada pembayaran ini
             * dikeluarkan dari akumulasi angsuran.
             */
            'administration_paid' =>
            max(
                round(
                    (float) $loan->administration_paid
                        - (float) $payment->administration_fee,
                    2
                ),
                0
            ),

            'status' =>
            $remainingPrincipal <= 0
                ? 'paid'
                : 'active',
        ]);
    }

    private function updateScheduledPayment(
        InstallmentPayment $payment,
        LoanInstallment $installment,
        Loan $loan,
        float $principal,
        float $profitShare,
        float $amount,
        Carbon $paymentDate
    ): void {
        $otherPayments = InstallmentPayment::query()
            ->where(
                'loan_installment_id',
                $installment->id
            )
            ->where(
                'id',
                '!=',
                $payment->id
            )
            ->lockForUpdate()
            ->get();

        $otherPaidAmount = round(
            (float) $otherPayments->sum('amount'),
            2
        );

        $remainingInstallment = max(
            round(
                (float) $installment->total_amount
                    - $otherPaidAmount,
                2
            ),
            0
        );

        if ($amount > $remainingInstallment) {
            throw ValidationException::withMessages([
                'principal_amount' =>
                'Total pembayaran maksimal Rp'
                    . number_format(
                        $remainingInstallment,
                        0,
                        ',',
                        '.'
                    )
                    . '.',
            ]);
        }

        $limits = $this->resolveComponentLimits(
            $payment
        );

        if ($principal > $limits['principal']) {
            throw ValidationException::withMessages([
                'principal_amount' =>
                'Angsuran pokok maksimal Rp'
                    . number_format(
                        $limits['principal'],
                        0,
                        ',',
                        '.'
                    )
                    . '.',
            ]);
        }

        if (
            $limits['profit_share'] !== null
            && $profitShare > $limits['profit_share']
        ) {
            throw ValidationException::withMessages([
                'profit_share_amount' =>
                'Bagi hasil maksimal Rp'
                    . number_format(
                        $limits['profit_share'],
                        0,
                        ',',
                        '.'
                    )
                    . '.',
            ]);
        }

        $newPaidAmount = round(
            $otherPaidAmount + $amount,
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

        if ($remainingAfter <= 0) {
            $installmentStatus = 'paid';
        } elseif ($installment->due_date->isPast()) {
            $installmentStatus = 'overdue';
        } else {
            $installmentStatus = 'partial';
        }

        $installment->update([
            'paid_amount' =>
            $newPaidAmount,

            'paid_at' =>
            $remainingAfter <= 0
                ? $paymentDate
                : null,

            'status' =>
            $installmentStatus,
        ]);

        $payment->update([
            'remaining_after' =>
            $remainingAfter,
        ]);

        $hasUnpaidInstallments = $loan
            ->installments()
            ->where(
                'status',
                '!=',
                'paid'
            )
            ->exists();

        $loan->update([
            'status' =>
            $hasUnpaidInstallments
                ? 'active'
                : 'paid',
        ]);
    }

    private function synchronizeCashTransaction(
        InstallmentPayment $payment,
        CashLedgerService $cashLedgerService
    ): void {
        $payment->loadMissing([
            'installment.loan.member',
        ]);

        $installment = $payment->installment;
        $loan = $installment->loan;
        $member = $loan->member;

        $cashTransaction = CashTransaction::query()
            ->where(
                'source_type',
                'installment_payment'
            )
            ->where(
                'source_id',
                $payment->id
            )
            ->lockForUpdate()
            ->first();

        if (!$cashTransaction) {
            $cashLedgerService
                ->recordInstallmentPayment(
                    $payment
                );

            return;
        }

        $cashTransaction->update([
            'transaction_date' =>
            $payment->payment_date,

            'direction' =>
            'income',

            'category' =>
            'Pembayaran Angsuran',

            'amount' =>
            $payment->amount,

            'payment_method' =>
            $payment->payment_method,

            'description' =>
            sprintf(
                'Pembayaran angsuran ke-%d pinjaman %s dari %s (%s).',
                $installment->installment_number,
                $loan->loan_number,
                $member->name,
                $member->member_number
            ),

            'user_id' =>
            $payment->user_id,
        ]);
    }

    private function rebuildJournal(
        InstallmentPayment $payment,
        AccountingJournalService $journalService
    ): void {
        $journal = JournalEntry::query()
            ->where(
                'source_type',
                'installment_payment'
            )
            ->where(
                'source_id',
                $payment->id
            )
            ->lockForUpdate()
            ->first();

        if ($journal) {
            $journal->lines()->delete();
            $journal->delete();
        }

        $journalService
            ->recordInstallmentPayment(
                $payment
            );
    }

    /**
     * Mendapatkan pembagian pembayaran lama.
     */
    private function resolveCurrentAllocation(
        InstallmentPayment $payment
    ): array {
        $amount = round(
            (float) $payment->amount,
            2
        );

        $principal = round(
            (float) $payment->principal_amount,
            2
        );

        $profitShare = round(
            (float) $payment->profit_share_amount,
            2
        );

        if (
            $principal <= 0
            && $profitShare <= 0
        ) {
            $scheduledPrincipal = round(
                (float) $payment
                    ->installment
                    ->principal_amount,
                2
            );

            $principal = min(
                $amount,
                $scheduledPrincipal
            );

            $profitShare = max(
                round(
                    $amount - $principal,
                    2
                ),
                0
            );
        }

        $allocated = round(
            $principal + $profitShare,
            2
        );

        if ($allocated < $amount) {
            $principal = round(
                $principal
                    + ($amount - $allocated),
                2
            );
        }

        return [
            'principal' =>
            $principal,

            'profit_share' =>
            $profitShare,
        ];
    }

    /**
     * Menghitung batas pokok dan bagi hasil yang
     * masih tersedia pada satu jadwal angsuran.
     */
    private function resolveComponentLimits(
        InstallmentPayment $payment
    ): array {
        $loan = $payment
            ->installment
            ->loan;

        $currentAllocation = $this
            ->resolveCurrentAllocation(
                $payment
            );

        if ((bool) $loan->is_legacy) {
            return [
                'principal' => round(
                    (float) $loan->outstanding_principal
                        + $currentAllocation['principal'],
                    2
                ),

                /*
                 * Bagi hasil legacy mengikuti catatan
                 * pembayaran koperasi.
                 */
                'profit_share' =>
                null,
            ];
        }

        $installment = $payment->installment;

        $otherPayments = InstallmentPayment::query()
            ->where(
                'loan_installment_id',
                $installment->id
            )
            ->where(
                'id',
                '!=',
                $payment->id
            )
            ->orderBy('payment_date')
            ->orderBy('id')
            ->get();

        $remainingPrincipal = round(
            (float) $installment->principal_amount,
            2
        );

        $remainingProfitShare = round(
            (float) $installment->interest_amount,
            2
        );

        foreach ($otherPayments as $otherPayment) {
            $otherAmount = round(
                (float) $otherPayment->amount,
                2
            );

            $otherPrincipal = round(
                (float) $otherPayment->principal_amount,
                2
            );

            $otherProfitShare = round(
                (float) $otherPayment->profit_share_amount,
                2
            );

            if (
                $otherPrincipal <= 0
                && $otherProfitShare <= 0
            ) {
                $otherPrincipal = min(
                    $otherAmount,
                    $remainingPrincipal
                );

                $otherProfitShare = min(
                    max(
                        round(
                            $otherAmount
                                - $otherPrincipal,
                            2
                        ),
                        0
                    ),
                    $remainingProfitShare
                );
            }

            $remainingPrincipal = max(
                round(
                    $remainingPrincipal
                        - $otherPrincipal,
                    2
                ),
                0
            );

            $remainingProfitShare = max(
                round(
                    $remainingProfitShare
                        - $otherProfitShare,
                    2
                ),
                0
            );
        }

        return [
            'principal' =>
            $remainingPrincipal,

            'profit_share' =>
            $remainingProfitShare,
        ];
    }

    private function isLatestLoanPayment(
        InstallmentPayment $payment
    ): bool {
        $loanId = $payment
            ->installment
            ->loan_id;

        $latestPaymentId = InstallmentPayment::query()
            ->whereHas(
                'installment',
                fn($query) => $query->where(
                    'loan_id',
                    $loanId
                )
            )
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->value('id');

        return (int) $latestPaymentId
            === (int) $payment->id;
    }
}
