<?php

namespace App\Services\Imports;

use App\Models\CashImportBatch;
use App\Models\CashImportRow;
use App\Models\CashTransaction;
use App\Models\LoanImportEntry;
use App\Models\SavingTransaction;
use DomainException;
use Illuminate\Support\Facades\DB;

class CashMonthlyImportService
{
    public function import(
        CashImportBatch $batch,
        ?int $userId
    ): array {
        return DB::transaction(function () use (
            $batch,
            $userId
        ): array {
            $batch = CashImportBatch::query()
                ->lockForUpdate()
                ->with('dataImportBatch')
                ->findOrFail($batch->id);

            $this->validateBatch($batch);

            $batch->update([
                'status' => 'processing',
                'error_message' => null,
            ]);

            $createdCount = 0;
            $dataBatch = $batch->dataImportBatch;

            /*
             * Hanya transaksi simpanan aktual.
             * Saldo awal dan adjustment tidak masuk kas.
             */
            $savingTransactions = SavingTransaction::query()
                ->with([
                    'member:id,name,member_number',
                    'savingType:id,name,code',
                ])
                ->where(
                    'import_batch_id',
                    $dataBatch->id
                )
                ->whereIn('import_component', [
                    'principal_deposit',
                    'mandatory_deposit',
                    'voluntary_deposit',
                    'voluntary_withdrawal',
                ])
                ->orderBy('transaction_date')
                ->get();

            foreach ($savingTransactions as $transaction) {
                $isDeposit = $transaction->transaction_type
                    === 'deposit';

                $category = match (
                    $transaction->import_component
                ) {
                    'principal_deposit'
                        => 'Setoran Simpanan Pokok',

                    'mandatory_deposit'
                        => 'Setoran Simpanan Wajib',

                    'voluntary_deposit'
                        => 'Setoran Simpanan Sukarela',

                    'voluntary_withdrawal'
                        => 'Penarikan Simpanan Sukarela',

                    default => 'Transaksi Simpanan',
                };

                $cash = CashTransaction::firstOrCreate(
                    [
                        'source_type' => 'saving_transaction',
                        'source_id' => $transaction->id,
                    ],
                    [
                        'transaction_date'
                            => $transaction->transaction_date,

                        'direction' => $isDeposit
                            ? 'income'
                            : 'expense',

                        'category' => $category,
                        'amount' => $transaction->amount,
                        'payment_method' => 'cash',

                        'description' => sprintf(
                            '%s anggota %s (%s), hasil migrasi.',
                            $category,
                            $transaction->member->name,
                            $transaction->member->member_number
                        ),

                        'user_id' => $userId,
                    ]
                );

                if ($cash->wasRecentlyCreated) {
                    $createdCount++;
                }
            }

            /*
             * Pembiayaan dan penerimaan dibuat dari audit entry,
             * bukan dari total principal Loan.
             */
            $loanEntries = LoanImportEntry::query()
                ->with([
                    'member:id,name,member_number',
                    'loan:id,loan_number',
                ])
                ->where(
                    'import_batch_id',
                    $dataBatch->id
                )
                ->orderBy('period_date')
                ->get();

            foreach ($loanEntries as $entry) {
                if ((float) $entry->new_financing > 0) {
                    $createdCount += $this->createCash(
                        sourceType: 'legacy_financing_disbursement',
                        sourceId: $entry->id,
                        date: $entry->period_date,
                        direction: 'expense',
                        category: 'Pencairan Pembiayaan',
                        amount: (float) $entry->new_financing,
                        description: sprintf(
                            'Pencairan pembiayaan %s kepada %s (%s), hasil migrasi.',
                            $entry->loan->loan_number,
                            $entry->member->name,
                            $entry->member->member_number
                        ),
                        userId: $userId
                    );
                }

                if ((float) $entry->principal_payment > 0) {
                    $createdCount += $this->createCash(
                        sourceType: 'legacy_principal_payment',
                        sourceId: $entry->id,
                        date: $entry->period_date,
                        direction: 'income',
                        category: 'Angsuran Pokok',
                        amount: (float) $entry->principal_payment,
                        description: sprintf(
                            'Angsuran pokok pembiayaan %s dari %s (%s), hasil migrasi.',
                            $entry->loan->loan_number,
                            $entry->member->name,
                            $entry->member->member_number
                        ),
                        userId: $userId
                    );
                }

                if ((float) $entry->profit_share > 0) {
                    $createdCount += $this->createCash(
                        sourceType: 'legacy_profit_share',
                        sourceId: $entry->id,
                        date: $entry->period_date,
                        direction: 'income',
                        category: 'Pendapatan Bagi Hasil',
                        amount: (float) $entry->profit_share,
                        description: sprintf(
                            'Bagi hasil pembiayaan %s dari %s (%s), hasil migrasi.',
                            $entry->loan->loan_number,
                            $entry->member->name,
                            $entry->member->member_number
                        ),
                        userId: $userId
                    );
                }

                if ((float) $entry->administration_fee > 0) {
                    $createdCount += $this->createCash(
                        sourceType: 'legacy_administration',
                        sourceId: $entry->id,
                        date: $entry->period_date,
                        direction: 'income',
                        category: 'Pendapatan Administrasi',
                        amount: (float) $entry->administration_fee,
                        description: sprintf(
                            'Administrasi pembiayaan %s dari %s (%s), hasil migrasi.',
                            $entry->loan->loan_number,
                            $entry->member->name,
                            $entry->member->member_number
                        ),
                        userId: $userId
                    );
                }
            }

            /*
             * Hanya biaya operasional yang belum memiliki
             * data rinci pada import anggota.
             */
            $cashRows = $batch->rows()
                ->orderBy('period_date')
                ->orderBy('row_number')
                ->get();

            foreach ($cashRows as $row) {
                if ((float) $row->transport_expense > 0) {
                    $createdCount += $this->createCash(
                        sourceType: 'cash_import_transport',
                        sourceId: $row->id,
                        date: $row->period_date,
                        direction: 'expense',
                        category: 'Biaya Transportasi',
                        amount: (float) $row->transport_expense,
                        description: $this->rowDescription(
                            $row,
                            'Biaya transportasi'
                        ),
                        userId: $userId
                    );
                }

                if ((float) $row->other_expense > 0) {
                    $createdCount += $this->createCash(
                        sourceType: 'cash_import_other_expense',
                        sourceId: $row->id,
                        date: $row->period_date,
                        direction: 'expense',
                        category: 'Biaya Operasional Lain',
                        amount: (float) $row->other_expense,
                        description: $this->rowDescription(
                            $row,
                            'Biaya operasional lain'
                        ),
                        userId: $userId
                    );
                }

                if ((float) $row->principal_refund > 0) {
                    $createdCount += $this->createCash(
                        sourceType: 'cash_import_principal_refund',
                        sourceId: $row->id,
                        date: $row->period_date,
                        direction: 'expense',
                        category: 'Pengembalian Simpanan Pokok',
                        amount: (float) $row->principal_refund,
                        description: $this->rowDescription(
                            $row,
                            'Pengembalian simpanan pokok'
                        ),
                        userId: $userId
                    );
                }

                if ((float) $row->mandatory_refund > 0) {
                    $createdCount += $this->createCash(
                        sourceType: 'cash_import_mandatory_refund',
                        sourceId: $row->id,
                        date: $row->period_date,
                        direction: 'expense',
                        category: 'Pengembalian Simpanan Wajib',
                        amount: (float) $row->mandatory_refund,
                        description: $this->rowDescription(
                            $row,
                            'Pengembalian simpanan wajib'
                        ),
                        userId: $userId
                    );
                }

                $row->update([
                    'status' => 'imported',
                    'message' => null,
                ]);
            }

            $batch->update([
                'status' => 'completed',
                'imported_cash_count' => $createdCount,
                'processed_at' => now(),
                'error_message' => null,
            ]);

            return [
                'created_count' => $createdCount,
            ];
        });
    }

    private function validateBatch(
        CashImportBatch $batch
    ): void {
        if ($batch->processed_at) {
            throw new DomainException(
                'Kas dari batch ini sudah pernah diproses.'
            );
        }

        if ($batch->status !== 'previewed') {
            throw new DomainException(
                'Batch kas belum siap diproses.'
            );
        }

        if (
            !$batch->dataImportBatch
                ?->members_savings_imported_at
            || !$batch->dataImportBatch
                ?->financing_imported_at
        ) {
            throw new DomainException(
                'Import anggota, simpanan, dan pembiayaan harus diselesaikan terlebih dahulu.'
            );
        }
    }

    private function createCash(
        string $sourceType,
        int $sourceId,
        mixed $date,
        string $direction,
        string $category,
        float $amount,
        string $description,
        ?int $userId
    ): int {
        $transaction = CashTransaction::firstOrCreate(
            [
                'source_type' => $sourceType,
                'source_id' => $sourceId,
            ],
            [
                'transaction_date' => $date,
                'direction' => $direction,
                'category' => $category,
                'amount' => round($amount, 2),
                'payment_method' => 'cash',
                'description' => $description,
                'user_id' => $userId,
            ]
        );

        return $transaction->wasRecentlyCreated
            ? 1
            : 0;
    }

    private function rowDescription(
        CashImportRow $row,
        string $default
    ): string {
        return sprintf(
            '%s: %s. Migrasi dari sheet %s baris %d.',
            $default,
            $row->description ?: 'Tanpa uraian',
            $row->sheet_name,
            $row->row_number
        );
    }
}
