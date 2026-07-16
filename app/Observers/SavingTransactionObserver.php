<?php

namespace App\Observers;

use App\Models\SavingTransaction;
use App\Services\Accounting\AccountingJournalService;

class SavingTransactionObserver
{
    public function created(
        SavingTransaction $savingTransaction
    ): void {
        app(
            AccountingJournalService::class
        )->recordSavingTransaction(
            $savingTransaction
        );
    }
}
