<?php

namespace App\Observers;

use App\Models\InstallmentPayment;
use App\Services\CashLedgerService;

class InstallmentPaymentObserver
{
    public function __construct(
        private readonly CashLedgerService $cashLedgerService
    ) {
    }

    public function created(
        InstallmentPayment $installmentPayment
    ): void {
        $this->cashLedgerService
            ->recordInstallmentPayment($installmentPayment);
    }
}
