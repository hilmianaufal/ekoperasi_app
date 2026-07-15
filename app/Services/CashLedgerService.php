<?php

namespace App\Services;

use App\Models\CashTransaction;
use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\SavingTransaction;

class CashLedgerService
{
    public function recordSavingTransaction(
        SavingTransaction $savingTransaction
    ): CashTransaction {
        $savingTransaction->loadMissing([
            'member:id,name,member_number',
            'savingType:id,name',
        ]);

        $isDeposit = $savingTransaction->transaction_type === 'deposit';

        return CashTransaction::firstOrCreate(
            [
                'source_type' => 'saving_transaction',
                'source_id' => $savingTransaction->id,
            ],
            [
                'transaction_date' => $savingTransaction->transaction_date,
                'direction' => $isDeposit ? 'income' : 'expense',
                'category' => $isDeposit
                    ? 'Setoran Simpanan'
                    : 'Penarikan Simpanan',
                'amount' => $savingTransaction->amount,
                'payment_method' => 'cash',
                'description' => sprintf(
                    '%s %s anggota %s (%s).',
                    $isDeposit ? 'Setoran' : 'Penarikan',
                    $savingTransaction->savingType->name,
                    $savingTransaction->member->name,
                    $savingTransaction->member->member_number
                ),
                'user_id' => $savingTransaction->user_id,
            ]
        );
    }

    public function recordLoanDisbursement(
        Loan $loan
    ): CashTransaction {
        $loan->loadMissing([
            'member:id,name,member_number',
        ]);

        return CashTransaction::firstOrCreate(
            [
                'source_type' => 'loan_disbursement',
                'source_id' => $loan->id,
            ],
            [
                'transaction_date' => $loan->start_date
                    ?? $loan->application_date,
                'direction' => 'expense',
                'category' => 'Pencairan Pinjaman',
                'amount' => $loan->principal_amount,
                'payment_method' => 'cash',
                'description' => sprintf(
                    'Pencairan pinjaman %s kepada %s (%s).',
                    $loan->loan_number,
                    $loan->member->name,
                    $loan->member->member_number
                ),
                'user_id' => $loan->approved_by
                    ?? $loan->created_by,
            ]
        );
    }

    public function recordInstallmentPayment(
        InstallmentPayment $payment
    ): CashTransaction {
        $payment->loadMissing([
            'installment.loan.member:id,name,member_number',
        ]);

        $installment = $payment->installment;
        $loan = $installment->loan;
        $member = $loan->member;

        return CashTransaction::firstOrCreate(
            [
                'source_type' => 'installment_payment',
                'source_id' => $payment->id,
            ],
            [
                'transaction_date' => $payment->payment_date,
                'direction' => 'income',
                'category' => 'Pembayaran Angsuran',
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'description' => sprintf(
                    'Pembayaran angsuran ke-%d pinjaman %s dari %s (%s).',
                    $installment->installment_number,
                    $loan->loan_number,
                    $member->name,
                    $member->member_number
                ),
                'user_id' => $payment->user_id,
            ]
        );
    }
}
