<?php

namespace App\Observers;

use App\Models\SavingTransaction;
use App\Services\CashLedgerService;

class SavingTransactionObserver
{
    public function __construct(
        private readonly CashLedgerService $cashLedgerService
    ) {
    }

    public function created(
        SavingTransaction $savingTransaction
    ): void {
        $this->cashLedgerService
            ->recordSavingTransaction($savingTransaction);
    }
}
