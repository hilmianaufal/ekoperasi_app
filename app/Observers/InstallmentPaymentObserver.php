<?php

namespace App\Observers;

use App\Models\InstallmentPayment;
use App\Services\Accounting\AccountingJournalService;

class InstallmentPaymentObserver
{
    public function created(
        InstallmentPayment $installmentPayment
    ): void {
        app(
            AccountingJournalService::class
        )->recordInstallmentPayment(
            $installmentPayment
        );
    }
}
