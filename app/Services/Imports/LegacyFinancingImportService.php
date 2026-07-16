<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\LoanImportEntry;
use App\Models\LoanInstallment;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class LegacyFinancingImportService
{
    /**
     * @return array{
     *     loan_count:int,
     *     installment_count:int,
     *     payment_count:int,
     *     entry_count:int
     * }
     */
    public function import(
        ImportBatch $batch,
        ?int $userId
    ): array {
        return DB::transaction(function () use (
            $batch,
            $userId
        ): array {
            $batch = ImportBatch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            $this->validateBatch($batch);

            $batch->update([
                'status' => 'processing',
                'error_message' => null,
            ]);

            $mappings = $batch->mappings()
                ->where('status', 'imported')
                ->whereNotNull('member_id')
                ->orderBy('source_number')
                ->get();

            $loanCount = 0;
            $installmentCount = 0;
            $paymentCount = 0;
            $entryCount = 0;

            foreach ($mappings as $mapping) {
                $rows = $batch->rows()
                    ->where(
                        'source_number',
                        $mapping->source_number
                    )
                    ->orderBy('period_date')
                    ->orderBy('row_number')
                    ->get();

                if ($rows->isEmpty()) {
                    continue;
                }

                $firstRow = $rows->first();
                $lastRow = $rows->last();

                $openingPrincipal = $this->money(
                    (float) $firstRow->remaining_financing
                    + (float) $firstRow->principal_installment
                    - (float) $firstRow->new_financing
                );

                if ($openingPrincipal < 0) {
                    $openingPrincipal = 0;
                }

                $totalNewFinancing = $this->money(
                    (float) $rows->sum('new_financing')
                );

                $totalPrincipalPayment = $this->money(
                    (float) $rows->sum('principal_installment')
                );

                $totalProfitShare = $this->money(
                    (float) $rows->sum('profit_share')
                );

                $totalAdministration = $this->money(
                    (float) $rows->sum('administration_fee')
                );

                $outstandingPrincipal = $this->money(
                    (float) $lastRow->remaining_financing
                );

                $hasActivity = (
                    $openingPrincipal > 0
                    || $totalNewFinancing > 0
                    || $totalPrincipalPayment > 0
                    || $totalProfitShare > 0
                    || $totalAdministration > 0
                    || $outstandingPrincipal > 0
                );

                if (!$hasActivity) {
                    continue;
                }

                $this->ensureMemberHasNoExistingLoan(
                    $mapping->member_id,
                    $batch
                );

                $openingDate = Carbon::parse(
                    $firstRow->period_date
                )
                    ->startOfMonth()
                    ->subDay();

                $activityMonthCount = $rows
                    ->filter(function (ImportRow $row): bool {
                        return (
                            (float) $row->new_financing > 0
                            || (float) $row->principal_installment > 0
                            || (float) $row->profit_share > 0
                            || (float) $row->administration_fee > 0
                            || (float) $row->remaining_financing > 0
                        );
                    })
                    ->count();

                $principalAmount = $this->money(
                    $openingPrincipal
                    + $totalNewFinancing
                );

                $loan = Loan::create([
                    'loan_number' => $this->loanNumber(
                        $batch,
                        $mapping->source_number
                    ),
                    'member_id' => $mapping->member_id,
                    'created_by' => $userId,
                    'approved_by' => $userId,
                    'import_batch_id' => $batch->id,
                    'source_number' => $mapping->source_number,
                    'is_legacy' => true,
                    'application_date' => $openingDate->toDateString(),
                    'principal_amount' => $principalAmount,
                    'opening_principal' => $openingPrincipal,
                    'disbursed_during_import' => $totalNewFinancing,
                    'outstanding_principal' => $outstandingPrincipal,
                    'profit_share_paid' => $totalProfitShare,
                    'administration_paid' => $totalAdministration,
                    'interest_rate' => 0,
                    'tenor_months' => max(
                        $activityMonthCount,
                        1
                    ),
                    'interest_amount' => $totalProfitShare,
                    'total_amount' => $this->money(
                        $principalAmount
                        + $totalProfitShare
                    ),
                    'monthly_installment' => 0,
                    'start_date' => $openingDate->toDateString(),
                    'maturity_date' => null,
                    'purpose' => 'Pembiayaan lama hasil migrasi',
                    'notes' => sprintf(
                        'Migrasi dari %s. Cut-off %s. Tenor dan tanggal akad asli belum tersedia.',
                        $batch->original_name,
                        $batch->cutoff_date?->format('d-m-Y')
                    ),
                    'status' => $outstandingPrincipal > 0
                        ? 'active'
                        : 'paid',
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);

                $loanCount++;

                $runningPrincipal = $openingPrincipal;
                $installmentNumber = 0;

                foreach ($rows as $row) {
                    $periodDate = Carbon::parse(
                        $row->period_date
                    );

                    $newFinancing = $this->money(
                        (float) $row->new_financing
                    );

                    $principalPayment = $this->money(
                        (float) $row->principal_installment
                    );

                    $profitShare = $this->money(
                        (float) $row->profit_share
                    );

                    $administrationFee = $this->money(
                        (float) $row->administration_fee
                    );

                    $reportedRemaining = $this->money(
                        (float) $row->remaining_financing
                    );

                    $calculatedRemaining = $this->money(
                        $runningPrincipal
                        + $newFinancing
                        - $principalPayment
                    );

                    $balanceAdjustment = $this->money(
                        $reportedRemaining
                        - $calculatedRemaining
                    );

                    LoanImportEntry::create([
                        'import_batch_id' => $batch->id,
                        'import_row_id' => $row->id,
                        'loan_id' => $loan->id,
                        'member_id' => $mapping->member_id,
                        'period_date' => $periodDate->toDateString(),
                        'opening_principal' => $runningPrincipal,
                        'new_financing' => $newFinancing,
                        'principal_payment' => $principalPayment,
                        'profit_share' => $profitShare,
                        'administration_fee' => $administrationFee,
                        'reported_remaining' => $reportedRemaining,
                        'calculated_remaining' => $calculatedRemaining,
                        'balance_adjustment' => $balanceAdjustment,
                        'notes' => abs($balanceAdjustment) >= 0.01
                            ? 'Terdapat penyesuaian agar saldo sama dengan rekapan Excel.'
                            : null,
                    ]);

                    $entryCount++;

                    $paymentAmount = $this->money(
                        $principalPayment
                        + $profitShare
                    );

                    if ($paymentAmount > 0) {
                        $installmentNumber++;

                        $installment = LoanInstallment::create([
                            'loan_id' => $loan->id,
                            'import_batch_id' => $batch->id,
                            'import_row_id' => $row->id,
                            'installment_number' => $installmentNumber,
                            'due_date' => $periodDate->toDateString(),
                            'principal_amount' => $principalPayment,
                            'interest_amount' => $profitShare,
                            'total_amount' => $paymentAmount,
                            'paid_amount' => $paymentAmount,
                            'reported_remaining_principal'
                                => $reportedRemaining,
                            'paid_at' => $periodDate
                                ->copy()
                                ->endOfDay(),
                            'status' => 'paid',
                            'notes' => sprintf(
                                'Angsuran migrasi dari sheet %s baris %d.',
                                $row->sheet_name,
                                $row->row_number
                            ),
                        ]);

                        $installmentCount++;

                        InstallmentPayment::create([
                            'payment_code' => $this->paymentCode(
                                $batch,
                                $row
                            ),
                            'loan_installment_id' => $installment->id,
                            'user_id' => $userId,
                            'import_batch_id' => $batch->id,
                            'import_row_id' => $row->id,
                            'payment_date' => $periodDate->toDateString(),
                            'amount' => $paymentAmount,
                            'principal_amount' => $principalPayment,
                            'profit_share_amount' => $profitShare,
                            'administration_fee' => $administrationFee,
                            'remaining_after' => 0,
                            'payment_method' => 'other',
                            'reference_number' => null,
                            'notes' => sprintf(
                                'Pembayaran migrasi dari %s, sheet %s, baris %d.',
                                $batch->original_name,
                                $row->sheet_name,
                                $row->row_number
                            ),
                        ]);

                        $paymentCount++;
                    }

                    $row->update([
                        'message' => abs($balanceAdjustment) >= 0.01
                            ? sprintf(
                                'Penyesuaian saldo pembiayaan: Rp%s.',
                                number_format(
                                    $balanceAdjustment,
                                    0,
                                    ',',
                                    '.'
                                )
                            )
                            : null,
                    ]);

                    /*
                     * Saldo Excel menjadi sumber utama
                     * untuk periode berikutnya.
                     */
                    $runningPrincipal = $reportedRemaining;
                }
            }

            $batch->update([
                'status' => 'previewed',
                'imported_loan_count' => $loanCount,
                'imported_installment_count' => $installmentCount,
                'imported_payment_count' => $paymentCount,
                'imported_financing_entry_count' => $entryCount,
                'financing_imported_at' => now(),
                'error_message' => null,
            ]);

            return [
                'loan_count' => $loanCount,
                'installment_count' => $installmentCount,
                'payment_count' => $paymentCount,
                'entry_count' => $entryCount,
            ];
        });
    }

    private function validateBatch(
        ImportBatch $batch
    ): void {
        if (!$batch->members_savings_imported_at) {
            throw new DomainException(
                'Import anggota dan simpanan harus diselesaikan terlebih dahulu.'
            );
        }

        if ($batch->financing_imported_at) {
            throw new DomainException(
                'Pembiayaan dari batch ini sudah pernah diimpor.'
            );
        }

        if ($batch->status !== 'previewed') {
            throw new DomainException(
                'Batch belum siap diproses.'
            );
        }

        if (
            Loan::query()
                ->where('import_batch_id', $batch->id)
                ->exists()
            || LoanImportEntry::query()
                ->where('import_batch_id', $batch->id)
                ->exists()
        ) {
            throw new DomainException(
                'Ditemukan data pembiayaan dari batch ini. Import dihentikan untuk mencegah data ganda.'
            );
        }

        $memberCount = $batch->mappings()
            ->where('status', 'imported')
            ->whereNotNull('member_id')
            ->count();

        if ($memberCount === 0) {
            throw new DomainException(
                'Tidak ada anggota hasil import yang dapat diproses.'
            );
        }
    }

    private function ensureMemberHasNoExistingLoan(
        int $memberId,
        ImportBatch $batch
    ): void {
        $exists = Loan::query()
            ->where('member_id', $memberId)
            ->where(function ($query) use ($batch): void {
                $query
                    ->whereNull('import_batch_id')
                    ->orWhere(
                        'import_batch_id',
                        '!=',
                        $batch->id
                    );
            })
            ->exists();

        if ($exists) {
            throw new DomainException(
                'Salah satu anggota sudah memiliki pembiayaan di aplikasi. Import dihentikan agar data tidak tercatat ganda.'
            );
        }
    }

    private function loanNumber(
        ImportBatch $batch,
        int $sourceNumber
    ): string {
        return sprintf(
            'MIG-LOAN-%06d-%04d',
            $batch->id,
            $sourceNumber
        );
    }

    private function paymentCode(
        ImportBatch $batch,
        ImportRow $row
    ): string {
        return sprintf(
            'MIG-PAY-%06d-%06d',
            $batch->id,
            $row->id
        );
    }

    private function money(
        float $value
    ): float {
        return round($value, 2);
    }
}
