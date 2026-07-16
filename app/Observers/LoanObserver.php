<?php

namespace App\Observers;

use App\Models\Loan;
use App\Services\Accounting\AccountingJournalService;

class LoanObserver
{
    public function created(
        Loan $loan
    ): void {
        if (
            in_array(
                $loan->status,
                [
                    'active',
                    'paid',
                ],
                true
            )
        ) {
            app(
                AccountingJournalService::class
            )->recordLoanDisbursement(
                $loan
            );
        }
    }

    public function updated(
        Loan $loan
    ): void {
        if (
            $loan->wasChanged('status')
            && in_array(
                $loan->status,
                [
                    'active',
                    'paid',
                ],
                true
            )
        ) {
            app(
                AccountingJournalService::class
            )->recordLoanDisbursement(
                $loan
            );
        }
    }
}
