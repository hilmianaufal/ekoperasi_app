<?php

require __DIR__ . '/vendor/autoload.php';

$laravel = require __DIR__ . '/bootstrap/app.php';

$laravel
    ->make(Illuminate\Contracts\Console\Kernel::class)
    ->bootstrap();

use App\Models\CashImportBatch;
use App\Models\CashImportRow;
use App\Models\SavingTransaction;

$cashBatch = CashImportBatch::findOrFail(1);
$dataBatchId = $cashBatch->data_import_batch_id;

$excelTotals = CashImportRow::query()
    ->where('cash_import_batch_id', $cashBatch->id)
    ->selectRaw("DATE_FORMAT(period_date, '%Y-%m') AS period")
    ->selectRaw('SUM(voluntary_deposit) AS deposit')
    ->selectRaw('SUM(voluntary_withdrawal) AS withdrawal')
    ->groupBy('period')
    ->orderBy('period')
    ->get()
    ->keyBy('period');

$appTotals = SavingTransaction::query()
    ->where('import_batch_id', $dataBatchId)
    ->whereIn('import_component', [
        'voluntary_deposit',
        'voluntary_withdrawal',
    ])
    ->selectRaw("DATE_FORMAT(transaction_date, '%Y-%m') AS period")
    ->selectRaw("
        SUM(
            CASE
                WHEN import_component = 'voluntary_deposit'
                THEN amount
                ELSE 0
            END
        ) AS deposit
    ")
    ->selectRaw("
        SUM(
            CASE
                WHEN import_component = 'voluntary_withdrawal'
                THEN amount
                ELSE 0
            END
        ) AS withdrawal
    ")
    ->groupBy('period')
    ->orderBy('period')
    ->get()
    ->keyBy('period');

$periods = $excelTotals
    ->keys()
    ->merge($appTotals->keys())
    ->unique()
    ->sort()
    ->values();

$differentPeriods = [];

echo "REKONSILIASI SIMPANAN SUKARELA\n";
echo "========================================\n\n";

foreach ($periods as $period) {
    $excelDeposit = (float) ($excelTotals[$period]->deposit ?? 0);
    $excelWithdrawal = (float) ($excelTotals[$period]->withdrawal ?? 0);

    $appDeposit = (float) ($appTotals[$period]->deposit ?? 0);
    $appWithdrawal = (float) ($appTotals[$period]->withdrawal ?? 0);

    $depositDifference = $appDeposit - $excelDeposit;
    $withdrawalDifference = $appWithdrawal - $excelWithdrawal;

    if (
        abs($depositDifference) < 0.01
        && abs($withdrawalDifference) < 0.01
    ) {
        continue;
    }

    $differentPeriods[] = $period;

    echo "PERIODE: {$period}\n";
    echo "----------------------------------------\n";

    echo "Setoran Excel      : Rp"
        . number_format($excelDeposit, 0, ',', '.')
        . "\n";

    echo "Setoran Aplikasi   : Rp"
        . number_format($appDeposit, 0, ',', '.')
        . "\n";

    echo "Selisih Setoran    : Rp"
        . number_format($depositDifference, 0, ',', '.')
        . "\n\n";

    echo "Penarikan Excel    : Rp"
        . number_format($excelWithdrawal, 0, ',', '.')
        . "\n";

    echo "Penarikan Aplikasi : Rp"
        . number_format($appWithdrawal, 0, ',', '.')
        . "\n";

    echo "Selisih Penarikan  : Rp"
        . number_format($withdrawalDifference, 0, ',', '.')
        . "\n\n";
}

echo "\nRINCIAN TRANSAKSI PADA BULAN BERBEDA\n";
echo "========================================\n\n";

$transactions = SavingTransaction::query()
    ->with('member:id,name,member_number')
    ->where('import_batch_id', $dataBatchId)
    ->whereIn('import_component', [
        'voluntary_deposit',
        'voluntary_withdrawal',
    ])
    ->orderBy('transaction_date')
    ->orderBy('id')
    ->get()
    ->filter(function ($transaction) use ($differentPeriods): bool {
        return in_array(
            $transaction->transaction_date->format('Y-m'),
            $differentPeriods,
            true
        );
    });

foreach ($transactions as $transaction) {
    echo "Tanggal : "
        . $transaction->transaction_date->format('Y-m-d')
        . "\n";

    echo "Anggota : "
        . ($transaction->member?->name ?? '-')
        . " ("
        . ($transaction->member?->member_number ?? '-')
        . ")\n";

    echo "Jenis   : "
        . $transaction->import_component
        . "\n";

    echo "Nominal : Rp"
        . number_format((float) $transaction->amount, 0, ',', '.')
        . "\n";

    echo "Sumber  : "
        . ($transaction->notes ?? '-')
        . "\n";

    echo "----------------------------------------\n";
}
