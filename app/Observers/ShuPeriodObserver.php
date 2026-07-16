<?php

namespace App\Observers;

use App\Models\ShuPeriod;
use App\Services\Accounting\AccountingJournalService;

class ShuPeriodObserver
{
    public function updated(
        ShuPeriod $shuPeriod
    ): void {
        if (
            $shuPeriod->wasChanged('status')
            && in_array(
                $shuPeriod->status,
                [
                    'approved',
                    'distributed',
                ],
                true
            )
        ) {
            app(
                AccountingJournalService::class
            )->recordShuAllocation(
                $shuPeriod
            );
        }
    }
}
