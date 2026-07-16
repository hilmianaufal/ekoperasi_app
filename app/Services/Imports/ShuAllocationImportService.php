<?php

namespace App\Services\Imports;

use App\Models\ShuImportBatch;
use App\Models\ShuMemberAllocation;
use DomainException;
use Illuminate\Support\Facades\DB;

class ShuAllocationImportService
{
    /**
     * @return array{
     *     allocation_count:int,
     *     business_service_total:float,
     *     saving_service_total:float,
     *     shu_total:float
     * }
     */
    public function import(
        ShuImportBatch $batch
    ): array {
        return DB::transaction(function () use (
            $batch
        ): array {
            $batch = ShuImportBatch::query()
                ->with('period')
                ->lockForUpdate()
                ->findOrFail($batch->id);

            $this->validateBatch($batch);

            $batch->update([
                'status' => 'processing',
                'error_message' => null,
            ]);

            $rows = $batch->rows()
                ->where('status', 'matched')
                ->whereNotNull('member_id')
                ->orderBy('source_number')
                ->get();

            $allocationCount = 0;
            $businessServiceTotal = 0.0;
            $savingServiceTotal = 0.0;
            $shuTotal = 0.0;

            foreach ($rows as $row) {
                /*
                 * Nilai yang digunakan adalah nilai pembagian
                 * yang tertulis pada file client.
                 *
                 * Nilai calculated tetap disimpan sebagai audit.
                 */
                ShuMemberAllocation::create([
                    'shu_period_id' =>
                        $batch->shu_period_id,

                    'shu_import_row_id' =>
                        $row->id,

                    'member_id' =>
                        $row->member_id,

                    'source_number' =>
                        $row->source_number,

                    'receivable_balance' =>
                        $row->receivable_balance,

                    'profit_share_base' =>
                        $row->profit_share_base,

                    'principal_saving' =>
                        $row->principal_saving,

                    'mandatory_saving' =>
                        $row->mandatory_saving,

                    'saving_balance' =>
                        $row->saving_balance,

                    'business_service_amount' =>
                        $row->source_business_service,

                    'saving_service_amount' =>
                        $row->source_saving_service,

                    'total_shu' =>
                        $row->source_total_shu,

                    'paid_amount' => 0,
                    'payment_status' => 'unpaid',

                    'notes' => abs(
                        (float) $row->difference
                    ) >= 0.01
                        ? sprintf(
                            'Nilai file dipakai. Terdapat selisih hitung ulang Rp%s.',
                            number_format(
                                (float) $row->difference,
                                0,
                                ',',
                                '.'
                            )
                        )
                        : null,
                ]);

                $row->update([
                    'status' => 'imported',
                ]);

                $allocationCount++;

                $businessServiceTotal +=
                    (float) $row
                        ->source_business_service;

                $savingServiceTotal +=
                    (float) $row
                        ->source_saving_service;

                $shuTotal +=
                    (float) $row
                        ->source_total_shu;
            }

            $batch->update([
                'status' => 'completed',
                'imported_count' => $allocationCount,
                'processed_at' => now(),
                'error_message' => null,
            ]);

            /*
             * Tetap berstatus review sampai pengurus
             * menyetujui hasil rekonsiliasi.
             */
            $batch->period->update([
                'status' => 'review',
            ]);

            return [
                'allocation_count' => $allocationCount,

                'business_service_total' => round(
                    $businessServiceTotal,
                    2
                ),

                'saving_service_total' => round(
                    $savingServiceTotal,
                    2
                ),

                'shu_total' => round(
                    $shuTotal,
                    2
                ),
            ];
        });
    }

    private function validateBatch(
        ShuImportBatch $batch
    ): void {
        if ($batch->processed_at) {
            throw new DomainException(
                'Batch SHU ini sudah pernah diproses.'
            );
        }

        if ($batch->status !== 'previewed') {
            throw new DomainException(
                'Batch SHU belum siap diproses.'
            );
        }

        if (
            $batch->period->status
            === 'approved'
            || $batch->period->status
            === 'distributed'
        ) {
            throw new DomainException(
                'Periode SHU sudah disetujui atau dibagikan.'
            );
        }

        if (
            ShuMemberAllocation::query()
                ->where(
                    'shu_period_id',
                    $batch->shu_period_id
                )
                ->exists()
        ) {
            throw new DomainException(
                'Periode ini sudah memiliki alokasi anggota.'
            );
        }

        $reviewCount = $batch->rows()
            ->whereIn('status', [
                'new',
                'review',
            ])
            ->count();

        if ($reviewCount > 0) {
            throw new DomainException(
                "Masih terdapat {$reviewCount} baris yang perlu diperiksa."
            );
        }

        $matchedCount = $batch->rows()
            ->where('status', 'matched')
            ->whereNotNull('member_id')
            ->count();

        if ($matchedCount === 0) {
            throw new DomainException(
                'Tidak ada data anggota SHU yang dapat diproses.'
            );
        }
    }
}
