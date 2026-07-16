<?php

namespace App\Providers;

use App\Models\AppSetting;
use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\SavingTransaction;
use App\Models\ShuPayment;
use App\Models\ShuPeriod;
use App\Observers\InstallmentPaymentObserver;
use App\Observers\LoanObserver;
use App\Observers\SavingTransactionObserver;
use App\Observers\ShuPaymentObserver;
use App\Observers\ShuPeriodObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        SavingTransaction::observe(
            SavingTransactionObserver::class
        );

        Loan::observe(
            LoanObserver::class
        );

        InstallmentPayment::observe(
            InstallmentPaymentObserver::class
        );

        SavingTransaction::observe(
            SavingTransactionObserver::class
        );

        Loan::observe(
            LoanObserver::class
        );

        InstallmentPayment::observe(
            InstallmentPaymentObserver::class
        );

        ShuPeriod::observe(
            ShuPeriodObserver::class
        );

        ShuPayment::observe(
            ShuPaymentObserver::class
        );
        View::composer('*', function ($view): void {
            $appSetting = null;

            try {
                if (Schema::hasTable('app_settings')) {
                    $appSetting = AppSetting::current();
                }
            } catch (Throwable) {
                $appSetting = null;
            }

            $view->with(
                'appSetting',
                $appSetting
            );
        });
    }
}
