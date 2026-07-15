<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CashTransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InstallmentController;
use App\Http\Controllers\InstallmentPaymentController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SavingTransactionController;
use App\Http\Controllers\SavingTypeController;
use App\Http\Controllers\SettingController;
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
