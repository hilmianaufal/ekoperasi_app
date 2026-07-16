<?php

namespace App\Services\Imports;

use App\Models\CashImportBatch;
use App\Models\CashTransaction;
use App\Models\LoanImportEntry;
use App\Models\SavingTransaction;

class CashImportReconciliationService
{
    public function build(
        CashImportBatch $batch
    ): array {
        $batch->loadMissing(
            'dataImportBatch'
        );

        $source = [
            'financing_expense' => $this->sumRows(
                $batch,
                'financing_expense'
            ),
            'principal_refund' => $this->sumRows(
                $batch,
                'principal_refund'
            ),
            'mandatory_refund' => $this->sumRows(
                $batch,
                'mandatory_refund'
            ),
            'voluntary_withdrawal' => $this->sumRows(
                $batch,
                'voluntary_withdrawal'
            ),
            'transport_expense' => $this->sumRows(
                $batch,
                'transport_expense'
            ),
            'other_expense' => $this->sumRows(
                $batch,
                'other_expense'
            ),
            'installment_income' => $this->sumRows(
                $batch,
                'installment_income'
            ),
            'profit_share_income' => $this->sumRows(
                $batch,
                'profit_share_income'
            ),
            'administration_income' => $this->sumRows(
                $batch,
                'administration_income'
            ),
            'principal_deposit' => $this->sumRows(
                $batch,
                'principal_deposit'
            ),
            'mandatory_deposit' => $this->sumRows(
                $batch,
                'mandatory_deposit'
            ),
            'voluntary_deposit' => $this->sumRows(
                $batch,
                'voluntary_deposit'
            ),
        ];

        $dataBatchId = $batch->data_import_batch_id;

        $savingIds = SavingTransaction::query()
            ->where('import_batch_id', $dataBatchId)
            ->pluck('id');

        $loanEntryIds = LoanImportEntry::query()
            ->where('import_batch_id', $dataBatchId)
            ->pluck('id');

        $rowIds = $batch->rows()
            ->pluck('id');

        $actual = [
            'financing_expense' => $this->sumCash(
                'legacy_financing_disbursement',
                $loanEntryIds
            ),

            'principal_refund' => $this->sumCash(
                'cash_import_principal_refund',
                $rowIds
            ),

            'mandatory_refund' => $this->sumCash(
                'cash_import_mandatory_refund',
                $rowIds
            ),

            'voluntary_withdrawal' =>
                $this->sumSavingCash(
                    $savingIds,
                    'voluntary_withdrawal'
                ),

            'transport_expense' => $this->sumCash(
                'cash_import_transport',
                $rowIds
            ),

            'other_expense' => $this->sumCash(
                'cash_import_other_expense',
                $rowIds
            ),

            'installment_income' => $this->sumCash(
                'legacy_principal_payment',
                $loanEntryIds
            ),

            'profit_share_income' => $this->sumCash(
                'legacy_profit_share',
                $loanEntryIds
            ),

            'administration_income' => $this->sumCash(
                'legacy_administration',
                $loanEntryIds
            ),

            'principal_deposit' =>
                $this->sumSavingCash(
                    $savingIds,
                    'principal_deposit'
                ),

            'mandatory_deposit' =>
                $this->sumSavingCash(
                    $savingIds,
                    'mandatory_deposit'
                ),

            'voluntary_deposit' =>
                $this->sumSavingCash(
                    $savingIds,
                    'voluntary_deposit'
                ),
        ];

        $labels = [
            'financing_expense'
                => 'Pencairan Pembiayaan',

            'principal_refund'
                => 'Pengembalian Simpanan Pokok',

            'mandatory_refund'
                => 'Pengembalian Simpanan Wajib',

            'voluntary_withdrawal'
                => 'Penarikan Simpanan Sukarela',

            'transport_expense'
                => 'Biaya Transportasi',

            'other_expense'
                => 'Biaya Operasional Lain',

            'installment_income'
                => 'Angsuran Pokok',

            'profit_share_income'
                => 'Bagi Hasil',

            'administration_income'
                => 'Administrasi',

            'principal_deposit'
                => 'Setoran Simpanan Pokok',

            'mandatory_deposit'
                => 'Setoran Simpanan Wajib',

            'voluntary_deposit'
                => 'Setoran Simpanan Sukarela',
        ];

        $expenseKeys = [
            'financing_expense',
            'principal_refund',
            'mandatory_refund',
            'voluntary_withdrawal',
            'transport_expense',
            'other_expense',
        ];

        $metrics = [];

        foreach ($labels as $key => $label) {
            $difference = round(
                $actual[$key] - $source[$key],
                2
            );

            $metrics[] = [
                'key' => $key,
                'label' => $label,
                'direction' => in_array(
                    $key,
                    $expenseKeys,
                    true
                )
                    ? 'expense'
                    : 'income',

                'source' => $source[$key],
                'actual' => $actual[$key],
                'difference' => $difference,
                'matched' => abs($difference) < 0.01,
            ];
        }

        $sourceIncome = collect($metrics)
            ->where('direction', 'income')
            ->sum('source');

        $actualIncome = collect($metrics)
            ->where('direction', 'income')
            ->sum('actual');

        $sourceExpense = collect($metrics)
            ->where('direction', 'expense')
            ->sum('source');

        $actualExpense = collect($metrics)
            ->where('direction', 'expense')
            ->sum('actual');

        return [
            'metrics' => $metrics,

            'summary' => [
                'metric_count' => count($metrics),

                'matched_count' => collect($metrics)
                    ->where('matched', true)
                    ->count(),

                'difference_count' => collect($metrics)
                    ->where('matched', false)
                    ->count(),

                'source_income' => $sourceIncome,
                'actual_income' => $actualIncome,

                'source_expense' => $sourceExpense,
                'actual_expense' => $actualExpense,

                'source_net' => $sourceIncome
                    - $sourceExpense,

                'actual_net' => $actualIncome
                    - $actualExpense,
            ],
        ];
    }

    private function sumRows(
        CashImportBatch $batch,
        string $column
    ): float {
        return round(
            (float) $batch->rows()
                ->sum($column),
            2
        );
    }

    private function sumCash(
        string $sourceType,
        mixed $sourceIds
    ): float {
        if ($sourceIds->isEmpty()) {
            return 0;
        }

        return round(
            (float) CashTransaction::query()
                ->where(
                    'source_type',
                    $sourceType
                )
                ->whereIn(
                    'source_id',
                    $sourceIds
                )
                ->sum('amount'),
            2
        );
    }

    private function sumSavingCash(
        mixed $savingIds,
        string $component
    ): float {
        if ($savingIds->isEmpty()) {
            return 0;
        }

        return round(
            (float) CashTransaction::query()
                ->join(
                    'saving_transactions',
                    function ($join): void {
                        $join
                            ->on(
                                'saving_transactions.id',
                                '=',
                                'cash_transactions.source_id'
                            )
                            ->where(
                                'cash_transactions.source_type',
                                '=',
                                'saving_transaction'
                            );
                    }
                )
                ->whereIn(
                    'saving_transactions.id',
                    $savingIds
                )
                ->where(
                    'saving_transactions.import_component',
                    $component
                )
                ->sum('cash_transactions.amount'),
            2
        );
    }
}
