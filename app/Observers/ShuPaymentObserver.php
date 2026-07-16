<?php

namespace App\Observers;

use App\Models\ShuPayment;
use App\Services\Accounting\AccountingJournalService;

class ShuPaymentObserver
{
    public function created(
        ShuPayment $shuPayment
    ): void {
        app(
            AccountingJournalService::class
        )->recordShuPayment(
            $shuPayment
        );
    }
}
