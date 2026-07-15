<?php

namespace App\Console\Commands;

use App\Models\CashTransaction;
use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\SavingTransaction;
use App\Services\CashLedgerService;
use Illuminate\Console\Command;

class SyncCashTransactions extends Command
{
    protected $signature = 'cash:sync';

    protected $description = 'Sinkronkan transaksi lama ke buku kas koperasi';

    public function handle(
        CashLedgerService $cashLedgerService
    ): int {
        $before = CashTransaction::count();

        $this->info('Menyinkronkan transaksi simpanan...');

        SavingTransaction::query()
            ->orderBy('id')
            ->chunkById(
                100,
                function ($transactions) use ($cashLedgerService): void {
                    foreach ($transactions as $transaction) {
                        $cashLedgerService
                            ->recordSavingTransaction($transaction);
                    }
                }
            );

        $this->info('Menyinkronkan pencairan pinjaman...');

        Loan::query()
            ->whereIn('status', [
                'active',
                'paid',
            ])
            ->orderBy('id')
            ->chunkById(
                100,
                function ($loans) use ($cashLedgerService): void {
                    foreach ($loans as $loan) {
                        $cashLedgerService
                            ->recordLoanDisbursement($loan);
                    }
                }
            );

        $this->info('Menyinkronkan pembayaran angsuran...');

        InstallmentPayment::query()
            ->orderBy('id')
            ->chunkById(
                100,
                function ($payments) use ($cashLedgerService): void {
                    foreach ($payments as $payment) {
                        $cashLedgerService
                            ->recordInstallmentPayment($payment);
                    }
                }
            );

        $after = CashTransaction::count();
        $created = $after - $before;

        $this->newLine();
        $this->info("Sinkronisasi selesai. {$created} transaksi kas ditambahkan.");
        $this->info("Total transaksi kas: {$after}.");

        return self::SUCCESS;
    }
}
