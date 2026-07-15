<?php

namespace App\Observers;

use App\Models\Loan;
use App\Services\CashLedgerService;

class LoanObserver
{
    public function __construct(
        private readonly CashLedgerService $cashLedgerService
    ) {
    }

    public function updated(Loan $loan): void
    {
        if (
            $loan->wasChanged('status')
            && $loan->status === 'active'
        ) {
            $this->cashLedgerService
                ->recordLoanDisbursement($loan);
        }
    }
}
