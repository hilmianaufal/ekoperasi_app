<?php

use App\Http\Controllers\AccountingAccountController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CashImportController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataImportController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FinancialStatementController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\InstallmentPaymentController;
use App\Http\Controllers\JournalEntryController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ManualInstallmentController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingTransactionController;
use App\Http\Controllers\SavingTypeController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShuImportController;
use App\Http\Controllers\ShuPaymentController;
use App\Http\Controllers\ShuPeriodController;
use App\Http\Controllers\ShuReportController;
use App\Http\Controllers\TrialBalanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->name('login.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    Route::resource('saving-types', SavingTypeController::class)
        ->except('show');

    Route::get('/savings/balance', [
        SavingTransactionController::class,
        'balance',
    ])->name('savings.balance');

    Route::get('/savings', [
        SavingTransactionController::class,
        'index',
    ])->name('savings.index');

    Route::get('/loans', [
        LoanController::class,
        'index',
    ])->name('loans.index');

    Route::get('/loans/create', [
        LoanController::class,
        'create',
    ])->name('loans.create');

    Route::post('/loans', [
        LoanController::class,
        'store',
    ])->name('loans.store');

    Route::get('/loans/{loan}', [
        LoanController::class,
        'show',
    ])->name('loans.show');

    Route::post('/loans/{loan}/approve', [
        LoanController::class,
        'approve',
    ])->name('loans.approve');

    Route::post('/loans/{loan}/reject', [
        LoanController::class,
        'reject',
    ])->name('loans.reject');

    Route::post('/loans/{loan}/cancel', [
        LoanController::class,
        'cancel',
    ])->name('loans.cancel');

    Route::get('/savings/create', [
        SavingTransactionController::class,
        'create',
    ])->name('savings.create');
    Route::get('/installments', [
        InstallmentController::class,
        'index',
    ])->name('installments.index');

    Route::get('/installments/{loanInstallment}/pay', [
        InstallmentController::class,
        'create',
    ])->name('installments.pay');

    Route::get('/cash-transactions', [
        CashTransactionController::class,
        'index',
    ])->name('cash-transactions.index');

    Route::get('/cash-transactions/create', [
        CashTransactionController::class,
        'create',
    ])->name('cash-transactions.create');

    Route::get('/data-imports', [
        DataImportController::class,
        'index',
    ])->name('data-imports.index');

    Route::post('/data-imports', [
        DataImportController::class,
        'store',
    ])->name('data-imports.store');

    Route::post(
        '/data-imports/{importBatch}/process-members-savings',
        [
            DataImportController::class,
            'processMembersSavings',
        ]
    )->name('data-imports.process-members-savings');

    Route::get('/cash-imports', [
        CashImportController::class,
        'index',
    ])->name('cash-imports.index');

    Route::post('/cash-imports', [
        CashImportController::class,
        'store',
    ])->name('cash-imports.store');

    Route::get('/cash-imports/{cashImportBatch}', [
        CashImportController::class,
        'show',
    ])->name('cash-imports.show');

    Route::get('/shu-periods', [
        ShuPeriodController::class,
        'index',
    ])->name('shu-periods.index');

    Route::post(
        '/shu-periods/{shuPeriod}/imports',
        [
            ShuImportController::class,
            'store',
        ]
    )->name('shu-imports.store');

    Route::get(
        '/shu-imports/{shuImportBatch}',
        [
            ShuImportController::class,
            'show',
        ]
    )->name('shu-imports.show');

    Route::put(
        '/shu-imports/{shuImportBatch}/rows',
        [
            ShuImportController::class,
            'updateRows',
        ]
    )->name('shu-imports.rows.update');

    Route::post(
        '/shu-imports/{shuImportBatch}/process',
        [
            ShuImportController::class,
            'process',
        ]
    )->name('shu-imports.process');

    Route::delete(
        '/shu-imports/{shuImportBatch}',
        [
            ShuImportController::class,
            'destroy',
        ]
    )->name('shu-imports.destroy');

    Route::post(
        '/shu-periods/{shuPeriod}/approve',
        [
            ShuPeriodController::class,
            'approve',
        ]
    )->name('shu-periods.approve');

    Route::get(
        '/shu-payments/{shuPayment}',
        [
            ShuPaymentController::class,
            'show',
        ]
    )->name('shu-payments.show');

    Route::get(
        '/shu-payments',
        [
            ShuPaymentController::class,
            'index',
        ]
    )->name('shu-payments.index');

    Route::post(
        '/shu-periods/{shuPeriod}/payments/bulk',
        [
            ShuPaymentController::class,
            'bulkStore',
        ]
    )->name('shu-payments.bulk-store');

    Route::post(
        '/shu-allocations/{shuMemberAllocation}/payments',
        [
            ShuPaymentController::class,
            'store',
        ]
    )->name('shu-payments.store');

    Route::get(
        '/shu-payments/{shuPayment}/receipt',
        [
            ShuPaymentController::class,
            'receipt',
        ]
    )->name('shu-payments.receipt');

    Route::get(
        '/shu-periods/{shuPeriod}/report',
        [
            ShuReportController::class,
            'show',
        ]
    )->name('shu-reports.show');

    Route::get(
        '/shu-periods/{shuPeriod}/report/print',
        [
            ShuReportController::class,
            'print',
        ]
    )->name('shu-reports.print');

    Route::get(
        '/accounting-accounts',
        [
            AccountingAccountController::class,
            'index',
        ]
    )->name('accounting-accounts.index');

    Route::get(
        '/journal-entries',
        [
            JournalEntryController::class,
            'index',
        ]
    )->name('journal-entries.index');

    Route::get(
        '/journal-entries/create',
        [
            JournalEntryController::class,
            'create',
        ]
    )->name('journal-entries.create');

    Route::get(
        '/trial-balance',
        [
            TrialBalanceController::class,
            'index',
        ]
    )->name('trial-balance.index');

    Route::get(
        '/trial-balance/print',
        [
            TrialBalanceController::class,
            'print',
        ]
    )->name('trial-balance.print');

    Route::get('/expenses', [
        ExpenseController::class,
        'index',
    ])->name('expenses.index');

    Route::get('/expenses/create', [
        ExpenseController::class,
        'create',
    ])->name('expenses.create');

    Route::get('/installment-payments/{installmentPayment}/edit', [
        InstallmentPaymentController::class,
        'edit',
    ])->name('installment-payments.edit');

    Route::put('/installment-payments/{installmentPayment}', [
        InstallmentPaymentController::class,
        'update',
    ])->name('installment-payments.update');

    Route::get('/loans/{loan}/edit', [
        LoanController::class,
        'edit',
    ])->name('loans.edit');

    Route::put('/loans/{loan}', [
        LoanController::class,
        'update',
    ])->name('loans.update');

    Route::post('/expenses', [
        ExpenseController::class,
        'store',
    ])->name('expenses.store');

    Route::get('/installments/manual/create', [
        ManualInstallmentController::class,
        'create',
    ])->name('manual-installments.create');

    Route::post('/installments/manual', [
        ManualInstallmentController::class,
        'store',
    ])->name('manual-installments.store');

    Route::get('/installments/{loanInstallment}/pay', [
        InstallmentController::class,
        'create',
    ])->name('installments.pay');

    Route::get(
        '/trial-balance/export',
        [
            TrialBalanceController::class,
            'export',
        ]
    )->name('trial-balance.export');

    Route::post(
        '/journal-entries',
        [
            JournalEntryController::class,
            'store',
        ]
    )->name('journal-entries.store');

    Route::get(
        '/journal-entries/{journalEntry}',
        [
            JournalEntryController::class,
            'show',
        ]
    )->name('journal-entries.show');

    Route::post(
        '/journal-entries/{journalEntry}/post',
        [
            JournalEntryController::class,
            'post',
        ]
    )->name('journal-entries.post');

    Route::post(
        '/journal-entries/{journalEntry}/reverse',
        [
            JournalEntryController::class,
            'reverse',
        ]
    )->name('journal-entries.reverse');

    Route::delete(
        '/journal-entries/{journalEntry}',
        [
            JournalEntryController::class,
            'destroy',
        ]
    )->name('journal-entries.destroy');

    Route::get(
        '/financial-statements',
        [
            FinancialStatementController::class,
            'index',
        ]
    )->name('financial-statements.index');

    Route::get(
        '/financial-statements/{financialStatementPeriod}',
        [
            FinancialStatementController::class,
            'show',
        ]
    )->name('financial-statements.show');

    Route::get(
        '/shu-periods/{shuPeriod}/report/export',
        [
            ShuReportController::class,
            'export',
        ]
    )->name('shu-reports.export');

    Route::get(
        '/shu-payments/{shuPayment}',
        [
            ShuPaymentController::class,
            'show',
        ]
    )->name('shu-payments.show');

    Route::get(
        '/shu-payments/{shuPayment}/receipt',
        [
            ShuPaymentController::class,
            'receipt',
        ]
    )->name('shu-payments.receipt');

    Route::post(
        '/shu-allocations/{shuMemberAllocation}/payments',
        [
            ShuPaymentController::class,
            'store',
        ]
    )->name('shu-payments.store');

    Route::post('/shu-periods', [
        ShuPeriodController::class,
        'store',
    ])->name('shu-periods.store');

    Route::get('/shu-periods/{shuPeriod}', [
        ShuPeriodController::class,
        'show',
    ])->name('shu-periods.show');

    Route::delete('/shu-periods/{shuPeriod}', [
        ShuPeriodController::class,
        'destroy',
    ])->name('shu-periods.destroy');

    Route::post(
        '/cash-imports/{cashImportBatch}/process',
        [
            CashImportController::class,
            'process',
        ]
    )->name('cash-imports.process');

    Route::delete(
        '/cash-imports/{cashImportBatch}',
        [
            CashImportController::class,
            'destroy',
        ]
    )->name('cash-imports.destroy');

    Route::get(
        '/data-imports/{importBatch}/reconciliation',
        [
            DataImportController::class,
            'reconciliation',
        ]
    )->name('data-imports.reconciliation');
    Route::get('/data-imports/{importBatch}', [
        DataImportController::class,
        'show',
    ])->name('data-imports.show');

    Route::post(
        '/data-imports/{importBatch}/process-financing',
        [
            DataImportController::class,
            'processFinancing',
        ]
    )->name('data-imports.process-financing');

    Route::put('/data-imports/{importBatch}/mappings', [
        DataImportController::class,
        'updateMappings',
    ])->name('data-imports.mappings.update');

    Route::delete('/data-imports/{importBatch}', [
        DataImportController::class,
        'destroy',
    ])->name('data-imports.destroy');

    Route::get('/reports', [
        ReportController::class,
        'index',
    ])->name('reports.index');
    Route::get('/settings', [
        SettingController::class,
        'edit',
    ])->name('settings.edit');

    Route::put('/settings', [
        SettingController::class,
        'update',
    ])->name('settings.update');
    Route::get('/reports/print', [
        ReportController::class,
        'print',
    ])->name('reports.print');

    Route::get('/reports/export', [
        ReportController::class,
        'export',
    ])->name('reports.export');

    Route::post('/cash-transactions', [
        CashTransactionController::class,
        'store',
    ])->name('cash-transactions.store');

    Route::post('/installments/{loanInstallment}/payments', [
        InstallmentController::class,
        'store',
    ])->name('installments.payments.store');

    Route::get('/installment-payments/{installmentPayment}', [
        InstallmentPaymentController::class,
        'show',
    ])->name('installment-payments.show');
    Route::post('/savings', [
        SavingTransactionController::class,
        'store',
    ])->name('savings.store');
    Route::resource('members', MemberController::class);
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
