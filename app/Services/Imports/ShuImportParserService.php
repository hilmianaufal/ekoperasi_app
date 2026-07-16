<?php

namespace App\Services\Imports;

use App\Models\Member;
use App\Models\ShuImportBatch;
use App\Models\ShuImportRow;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Normalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class ShuImportParserService
{
    public function parse(
        ShuImportBatch $batch
    ): void {
        $batch->loadMissing('period');

        $filePath = Storage::disk('local')
            ->path($batch->stored_path);

        if (!is_file($filePath)) {
            throw new RuntimeException(
                'File Excel SHU tidak ditemukan.'
            );
        }

        $reader = IOFactory::createReaderForFile(
            $filePath
        );

        $reader->setReadDataOnly(false);

        $spreadsheet = $reader->load(
            $filePath
        );

        $worksheet = $spreadsheet
            ->getSheetByName('Sheet1')
            ?? $spreadsheet->getSheet(0);

        $headerRow = $this->findHeaderRow(
            $worksheet
        );

        if (!$headerRow) {
            throw new RuntimeException(
                'Header NO dan NAMA tidak ditemukan pada file SHU.'
            );
        }

        $warnings = [];
        $rowCount = 0;
        $matchedCount = 0;
        $reviewCount = 0;

        DB::transaction(function () use (
            $batch,
            $worksheet,
            $headerRow,
            &$warnings,
            &$rowCount,
            &$matchedCount,
            &$reviewCount
        ): void {
            $batch->rows()->delete();

            $highestRow = $worksheet
                ->getHighestDataRow();

            /*
             * Header utama berada pada baris pertama,
             * lalu ada subheader POKOK, WAJIB, JASUS, dan JASIM.
             */
            for (
                $rowNumber = $headerRow + 2;
                $rowNumber <= $highestRow;
                $rowNumber++
            ) {
                $sourceNumberValue = trim(
                    (string) $worksheet
                        ->getCell("A{$rowNumber}")
                        ->getValue()
                );

                if (
                    mb_strtoupper($sourceNumberValue)
                    === 'JUMLAH'
                ) {
                    break;
                }

                $sourceNumber = is_numeric(
                    $sourceNumberValue
                )
                    ? (int) $sourceNumberValue
                    : null;

                $sourceName = trim(
                    (string) $worksheet
                        ->getCell("B{$rowNumber}")
                        ->getValue()
                );

                if ($sourceName === '') {
                    continue;
                }

                $values = [
                    'receivable_balance' => $this->cellNumber(
                        $worksheet,
                        "C{$rowNumber}"
                    ),

                    'profit_share_base' => $this->cellNumber(
                        $worksheet,
                        "D{$rowNumber}"
                    ),

                    'principal_saving' => $this->cellNumber(
                        $worksheet,
                        "E{$rowNumber}"
                    ),

                    'mandatory_saving' => $this->cellNumber(
                        $worksheet,
                        "F{$rowNumber}"
                    ),

                    'saving_balance' => $this->cellNumber(
                        $worksheet,
                        "G{$rowNumber}"
                    ),

                    'source_business_service' => $this->cellNumber(
                        $worksheet,
                        "H{$rowNumber}"
                    ),

                    'source_saving_service' => $this->cellNumber(
                        $worksheet,
                        "I{$rowNumber}"
                    ),

                    'source_total_shu' => $this->cellNumber(
                        $worksheet,
                        "J{$rowNumber}"
                    ),
                ];

                $hasFinancialData = collect(
                    $values
                )->contains(
                    fn(float $value): bool =>
                    abs($value) >= 0.01
                );

                /*
                 * Baris SRIYATI bernomor 33 tidak memiliki
                 * data keuangan sehingga tidak diimpor.
                 */
                if (!$hasFinancialData) {
                    $warnings[] = [
                        'type' => 'empty_member_row',
                        'row_number' => $rowNumber,
                        'message' => sprintf(
                            'Baris %d atas nama %s dilewati karena seluruh nominal kosong.',
                            $rowNumber,
                            $sourceName
                        ),
                    ];

                    continue;
                }

                $normalizedName = $this->normalizeName(
                    $sourceName
                );

                [
                    $member,
                    $memberStatus,
                    $memberMessage,
                ] = $this->resolveMember(
                    $sourceNumber,
                    $normalizedName,
                    $sourceName
                );

                $calculatedBusinessService = $this->money(
                    $values['profit_share_base']
                        * (
                            (float) $batch
                                ->period
                                ->business_service_rate
                            / 100
                        )
                );

                $calculatedSavingService = $this->money(
                    $values['saving_balance']
                        * (
                            (float) $batch
                                ->period
                                ->saving_service_rate
                            / 100
                        )
                );

                $calculatedTotalShu = $this->money(
                    $calculatedBusinessService
                        + $calculatedSavingService
                );

                /*
                 * Selisih positif berarti total di file lebih besar
                 * daripada hasil hitung ulang aplikasi.
                 */
                $difference = $this->money(
                    $values['source_total_shu']
                        - $calculatedTotalShu
                );

                $status = $memberStatus;
                $notes = [];

                if ($memberMessage) {
                    $notes[] = $memberMessage;
                }

                if (abs($difference) >= 0.01) {
                    $status = 'review';

                    $notes[] = sprintf(
                        'Total SHU file berbeda Rp%s dari hasil hitung ulang.',
                        number_format(
                            $difference,
                            0,
                            ',',
                            '.'
                        )
                    );

                    $warnings[] = [
                        'type' => 'calculation_difference',
                        'row_number' => $rowNumber,
                        'source_number' => $sourceNumber,
                        'source_name' => $sourceName,
                        'difference' => $difference,
                        'message' => sprintf(
                            '%s memiliki selisih perhitungan SHU sebesar Rp%s.',
                            $sourceName,
                            number_format(
                                $difference,
                                0,
                                ',',
                                '.'
                            )
                        ),
                    ];
                }

                ShuImportRow::create([
                    'shu_import_batch_id' => $batch->id,
                    'sheet_name' => $worksheet->getTitle(),
                    'row_number' => $rowNumber,
                    'source_number' => $sourceNumber,
                    'source_name' => $sourceName,
                    'normalized_name' => $normalizedName,
                    'member_id' => $member?->id,

                    ...$values,

                    'calculated_business_service'
                    => $calculatedBusinessService,

                    'calculated_saving_service'
                    => $calculatedSavingService,

                    'calculated_total_shu'
                    => $calculatedTotalShu,

                    'difference' => $difference,
                    'status' => $status,

                    'raw_data' => [
                        'NO' => $sourceNumber,
                        'NAMA' => $sourceName,
                        'SALDO PIUTANG' =>
                        $values['receivable_balance'],
                        'BAGI HASIL' =>
                        $values['profit_share_base'],
                        'SIMPANAN POKOK' =>
                        $values['principal_saving'],
                        'SIMPANAN WAJIB' =>
                        $values['mandatory_saving'],
                        'JUMLAH SIMPANAN' =>
                        $values['saving_balance'],
                        'JASUS' =>
                        $values['source_business_service'],
                        'JASIM' =>
                        $values['source_saving_service'],
                        'JUMLAH SHU' =>
                        $values['source_total_shu'],
                    ],

                    'notes' => empty($notes)
                        ? null
                        : implode(' ', $notes),
                ]);

                $rowCount++;

                if ($status === 'matched') {
                    $matchedCount++;
                } else {
                    $reviewCount++;
                }
            }

            if ($rowCount === 0) {
                throw new RuntimeException(
                    'Tidak ada data anggota SHU yang dapat dibaca.'
                );
            }

            $batch->update([
                'status' => 'previewed',
                'row_count' => $rowCount,
                'matched_count' => $matchedCount,
                'review_count' => $reviewCount,
                'warnings' => $warnings,
                'error_message' => null,
            ]);
        });

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    private function findHeaderRow(
        Worksheet $worksheet
    ): ?int {
        $highestRow = min(
            $worksheet->getHighestDataRow(),
            20
        );

        for (
            $rowNumber = 1;
            $rowNumber <= $highestRow;
            $rowNumber++
        ) {
            $columnA = mb_strtoupper(
                trim(
                    (string) $worksheet
                        ->getCell("A{$rowNumber}")
                        ->getValue()
                )
            );

            $columnB = mb_strtoupper(
                trim(
                    (string) $worksheet
                        ->getCell("B{$rowNumber}")
                        ->getValue()
                )
            );

            if (
                $columnA === 'NO'
                && $columnB === 'NAMA'
            ) {
                return $rowNumber;
            }
        }

        return null;
    }

    /**
     * @return array{
     *     0:?Member,
     *     1:string,
     *     2:?string
     * }
     */
    private function resolveMember(
        ?int $sourceNumber,
        string $normalizedName,
        string $sourceName
    ): array {
        /*
         * Anggota hasil migrasi sebelumnya memakai format:
         * AGT-0001, AGT-0002, dan seterusnya.
         */
        if ($sourceNumber) {
            $memberNumber = 'AGT-' . str_pad(
                (string) $sourceNumber,
                4,
                '0',
                STR_PAD_LEFT
            );

            $member = Member::query()
                ->where(
                    'member_number',
                    $memberNumber
                )
                ->first();

            if ($member) {
                $memberNormalizedName =
                    $this->normalizeName(
                        $member->name
                    );

                if (
                    $memberNormalizedName
                    === $normalizedName
                ) {
                    return [
                        $member,
                        'matched',
                        null,
                    ];
                }

                return [
                    $member,
                    'review',
                    sprintf(
                        'Nama file "%s" berbeda dengan anggota aplikasi "%s".',
                        $sourceName,
                        $member->name
                    ),
                ];
            }
        }

        $sameNameMembers = Member::query()
            ->get([
                'id',
                'member_number',
                'name',
            ])
            ->filter(
                fn(Member $member): bool =>
                $this->normalizeName(
                    $member->name
                ) === $normalizedName
            )
            ->values();

        if ($sameNameMembers->count() === 1) {
            return [
                $sameNameMembers->first(),
                'matched',
                null,
            ];
        }

        if ($sameNameMembers->count() > 1) {
            return [
                null,
                'review',
                'Nama yang sama ditemukan lebih dari satu kali di data anggota.',
            ];
        }

        return [
            null,
            'review',
            'Anggota belum ditemukan di aplikasi.',
        ];
    }

    public function normalizeName(
        string $name
    ): string {
        $name = trim($name);

        if (class_exists(Normalizer::class)) {
            $name = Normalizer::normalize(
                $name,
                Normalizer::FORM_KD
            ) ?: $name;
        }

        $name = str_replace(
            ['’', '`', '“', '”'],
            ["'", "'", '"', '"'],
            $name
        );

        $name = mb_strtoupper(
            $name,
            'UTF-8'
        );

        $name = preg_replace(
            '/[^\pL\pN]+/u',
            ' ',
            $name
        ) ?: $name;

        return preg_replace(
            '/\s+/u',
            ' ',
            trim($name)
        ) ?: trim($name);
    }

    private function cellNumber(
        Worksheet $worksheet,
        string $coordinate
    ): float {
        $cell = $worksheet->getCell(
            $coordinate
        );

        $value = $cell->getValue();

        /*
     * Beberapa kolom SHU menggunakan formula bersama
     * seperti:
     *
     * =D8*0.18
     * =G8*0.06
     * =H8+I8
     *
     * Ambil hasil terakhir yang tersimpan di Excel,
     * bukan teks formulanya.
     */
        if (
            is_string($value)
            && str_starts_with($value, '=')
        ) {
            $cachedValue = $cell
                ->getOldCalculatedValue();

            if (
                $cachedValue !== null
                && $cachedValue !== ''
            ) {
                $value = $cachedValue;
            } else {
                try {
                    $value = $cell
                        ->getCalculatedValue();
                } catch (\Throwable) {
                    $value = 0;
                }
            }
        }

        return $this->number(
            $value
        );
    }

    private function number(
        mixed $value
    ): float {
        if (
            $value === null
            || $value === ''
            || $value === '-'
        ) {
            return 0;
        }

        if (is_numeric($value)) {
            return $this->money(
                (float) $value
            );
        }

        $value = str_ireplace(
            ['Rp', 'IDR', ' '],
            '',
            trim((string) $value)
        );

        $value = str_replace(
            '.',
            '',
            $value
        );

        $value = str_replace(
            ',',
            '.',
            $value
        );

        return is_numeric($value)
            ? $this->money((float) $value)
            : 0;
    }

    private function money(
        float $value
    ): float {
        return round($value, 2);
    }
}
