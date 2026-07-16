<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\ImportMemberMapping;
use App\Models\ImportRow;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Normalizer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class RekapAngsuranImportService
{
    private const MONTHS = [
        'JANUARI' => 1,
        'FEBRUARI' => 2,
        'MARET' => 3,
        'APRIL' => 4,
        'MEI' => 5,
        'JUNI' => 6,
        'JULI' => 7,
        'AGUSTUS' => 8,
        'SEPTEMBER' => 9,
        'OKTOBER' => 10,
        'NOVEMBER' => 11,
        'DESEMBER' => 12,
    ];

    public function parse(ImportBatch $batch): void
    {
        $filePath = Storage::disk('local')
            ->path($batch->stored_path);

        if (!is_file($filePath)) {
            throw new RuntimeException(
                'File Excel tidak ditemukan pada penyimpanan server.'
            );
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        $eligibleSheets = [];
        $memberCandidates = [];
        $warnings = [];
        $rowCount = 0;

        DB::transaction(function () use (
            $batch,
            $spreadsheet,
            &$eligibleSheets,
            &$memberCandidates,
            &$warnings,
            &$rowCount
        ): void {
            $batch->rows()->delete();
            $batch->mappings()->delete();

            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                $periodDate = $this->resolvePeriodDate(
                    $worksheet->getTitle(),
                    $batch->cutoff_date
                );

                if (!$periodDate) {
                    $warnings[] = [
                        'type' => 'unknown_sheet',
                        'message' => sprintf(
                            'Sheet "%s" tidak dikenali sebagai nama bulan.',
                            $worksheet->getTitle()
                        ),
                    ];

                    continue;
                }

                if (
                    $batch->cutoff_date
                    && $periodDate->gt($batch->cutoff_date)
                ) {
                    continue;
                }

                $eligibleSheets[] = $worksheet->getTitle();

                $sheetRows = $this->readWorksheet(
                    $batch,
                    $worksheet,
                    $periodDate,
                    $memberCandidates
                );

                $rowCount += $sheetRows;
            }

            if (empty($eligibleSheets)) {
                throw new RuntimeException(
                    'Tidak ada sheet yang dapat dibaca sesuai tanggal cut-off.'
                );
            }

            $this->createMappings(
                $batch,
                $memberCandidates,
                $warnings
            );

            $batch->update([
                'status' => 'previewed',
                'sheet_count' => count($eligibleSheets),
                'row_count' => $rowCount,
                'member_count' => count($memberCandidates),
                'warnings' => $warnings,
                'error_message' => null,
            ]);
        });

        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
    }

    private function readWorksheet(
        ImportBatch $batch,
        Worksheet $worksheet,
        Carbon $periodDate,
        array &$memberCandidates
    ): int {
        $createdRows = 0;
        $highestRow = $worksheet->getHighestDataRow();

        /*
         * Format file client:
         * baris 5 = header
         * baris 7 dan seterusnya = data anggota
         */
        for ($rowNumber = 7; $rowNumber <= $highestRow; $rowNumber++) {
            $sourceNumber = (int) $this->number(
                $worksheet->getCell("A{$rowNumber}")->getValue()
            );

            $sourceName = trim(
                (string) $worksheet
                    ->getCell("B{$rowNumber}")
                    ->getValue()
            );

            if ($sourceNumber <= 0 || $sourceName === '') {
                continue;
            }

            $normalizedName = $this->normalizeName($sourceName);

            $payload = [
                'import_batch_id' => $batch->id,
                'sheet_name' => $worksheet->getTitle(),
                'row_number' => $rowNumber,
                'period_date' => $periodDate->toDateString(),
                'source_number' => $sourceNumber,
                'source_name' => $sourceName,
                'normalized_name' => $normalizedName,

                'principal_saving' => $this->cellNumber(
                    $worksheet,
                    "C{$rowNumber}"
                ),

                'mandatory_saving' => $this->cellNumber(
                    $worksheet,
                    "D{$rowNumber}"
                ),

                'mandatory_balance' => $this->cellNumber(
                    $worksheet,
                    "E{$rowNumber}"
                ),

                'principal_installment' => $this->cellNumber(
                    $worksheet,
                    "F{$rowNumber}"
                ),

                'profit_share' => $this->cellNumber(
                    $worksheet,
                    "G{$rowNumber}"
                ),

                'accumulated_profit_share' => $this->cellNumber(
                    $worksheet,
                    "H{$rowNumber}"
                ),

                'remaining_financing' => $this->cellNumber(
                    $worksheet,
                    "I{$rowNumber}"
                ),

                'voluntary_saving' => $this->cellNumber(
                    $worksheet,
                    "J{$rowNumber}"
                ),

                'voluntary_balance' => $this->cellNumber(
                    $worksheet,
                    "K{$rowNumber}"
                ),

                'voluntary_withdrawal' => $this->cellNumber(
                    $worksheet,
                    "L{$rowNumber}"
                ),

                'administration_fee' => $this->cellNumber(
                    $worksheet,
                    "M{$rowNumber}"
                ),

                'new_financing' => $this->cellNumber(
                    $worksheet,
                    "N{$rowNumber}"
                ),
            ];

            $payload['raw_data'] = [
                'NO' => $sourceNumber,
                'NAMA' => $sourceName,
                'SMPN POKOK' => $payload['principal_saving'],
                'SMPN WAJIB' => $payload['mandatory_saving'],
                'JML SMPN WAJIB' => $payload['mandatory_balance'],
                'ANGSURAN' => $payload['principal_installment'],
                'BAGI HASIL' => $payload['profit_share'],
                'JUMLAH BAGI HASIL' => $payload[
                    'accumulated_profit_share'
                ],
                'SISA PINJAMAN' => $payload['remaining_financing'],
                'TABUNGAN SUKARELA' => $payload[
                    'voluntary_saving'
                ],
                'JUMLAH TABUNGAN SS' => $payload[
                    'voluntary_balance'
                ],
                'TABUNGAN KELUAR' => $payload[
                    'voluntary_withdrawal'
                ],
                'ADMIN' => $payload['administration_fee'],
                'PEMBIAYAAN' => $payload['new_financing'],
            ];

            ImportRow::create($payload);

            if (!isset($memberCandidates[$sourceNumber])) {
                $memberCandidates[$sourceNumber] = [
                    'names' => [],
                    'latest_name' => $sourceName,
                    'latest_period' => $periodDate->copy(),
                ];
            }

            $memberCandidates[$sourceNumber]['names'][
                $normalizedName
            ] = $sourceName;

            if (
                $periodDate->gte(
                    $memberCandidates[$sourceNumber]['latest_period']
                )
            ) {
                $memberCandidates[$sourceNumber]['latest_name']
                    = $sourceName;

                $memberCandidates[$sourceNumber]['latest_period']
                    = $periodDate->copy();
            }

            $createdRows++;
        }

        return $createdRows;
    }

    private function createMappings(
        ImportBatch $batch,
        array $memberCandidates,
        array &$warnings
    ): void {
        $existingMembers = Member::query()
            ->get(['id', 'name'])
            ->groupBy(
                fn (Member $member) => $this->normalizeName(
                    $member->name
                )
            );

        ksort($memberCandidates);

        foreach ($memberCandidates as $sourceNumber => $candidate) {
            $detectedNames = array_values($candidate['names']);

            $canonicalName = $candidate['latest_name'];
            $normalizedName = $this->normalizeName($canonicalName);

            $matchedMember = null;

            foreach (array_keys($candidate['names']) as $nameKey) {
                $members = $existingMembers->get($nameKey);

                if ($members && $members->count() === 1) {
                    $matchedMember = $members->first();
                    break;
                }
            }

            $hasNameVariation = count(
                $candidate['names']
            ) > 1;

            if ($hasNameVariation) {
                $status = 'review';

                $warnings[] = [
                    'type' => 'name_variation',
                    'source_number' => $sourceNumber,
                    'names' => $detectedNames,
                    'message' => sprintf(
                        'Nomor %d memiliki perbedaan penulisan nama: %s.',
                        $sourceNumber,
                        implode(', ', $detectedNames)
                    ),
                ];
            } elseif ($matchedMember) {
                $status = 'matched';
            } else {
                $status = 'new';
            }

            ImportMemberMapping::create([
                'import_batch_id' => $batch->id,
                'source_number' => $sourceNumber,
                'detected_names' => $detectedNames,
                'canonical_name' => $canonicalName,
                'normalized_name' => $normalizedName,
                'member_id' => $matchedMember?->id,
                'status' => $status,
            ]);

            ImportRow::query()
                ->where('import_batch_id', $batch->id)
                ->where('source_number', $sourceNumber)
                ->update([
                    'canonical_name' => $canonicalName,
                    'status' => $status === 'ignored'
                        ? 'skipped'
                        : 'ready',
                ]);
        }
    }

    private function resolvePeriodDate(
        string $sheetName,
        ?Carbon $cutoffDate
    ): ?Carbon {
        $normalizedSheet = strtoupper(
            trim($sheetName)
        );

        $monthNumber = null;

        foreach (self::MONTHS as $monthName => $number) {
            if (str_contains($normalizedSheet, $monthName)) {
                $monthNumber = $number;
                break;
            }
        }

        if (!$monthNumber) {
            return null;
        }

        preg_match(
            '/\b(20\d{2})\b/',
            $normalizedSheet,
            $yearMatch
        );

        $year = isset($yearMatch[1])
            ? (int) $yearMatch[1]
            : (int) (
                $cutoffDate?->year
                ?? now()->year
            );

        return Carbon::create(
            $year,
            $monthNumber,
            1
        )->endOfMonth();
    }

    public function normalizeName(string $name): string
    {
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
            '/[^\pL\pN\/\.\'\-\s]+/u',
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
        return $this->number(
            $worksheet->getCell($coordinate)->getValue()
        );
    }

    private function number(mixed $value): float
    {
        if (
            $value === null
            || $value === ''
            || $value === '-'
            || $value === ' '
        ) {
            return 0;
        }

        if (is_numeric($value)) {
            return round((float) $value, 2);
        }

        $value = trim((string) $value);
        $negative = false;

        if (
            str_starts_with($value, '(')
            && str_ends_with($value, ')')
        ) {
            $negative = true;
            $value = trim($value, '()');
        }

        $value = str_ireplace(
            ['Rp', 'IDR', ' '],
            '',
            $value
        );

        if (
            str_contains($value, '.')
            && !str_contains($value, ',')
        ) {
            $value = str_replace('.', '', $value);
        } else {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        $number = is_numeric($value)
            ? (float) $value
            : 0;

        return round(
            $negative ? -$number : $number,
            2
        );
    }
}
