<?php

namespace App\Services\Imports;

use App\Models\CashImportBatch;
use App\Models\CashImportRow;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use RuntimeException;

class CashMonthlyParserService
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

    public function parse(CashImportBatch $batch): void
    {
        $filePath = Storage::disk('local')
            ->path($batch->stored_path);

        if (!is_file($filePath)) {
            throw new RuntimeException(
                'File Excel kas tidak ditemukan.'
            );
        }

        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);

        $spreadsheet = $reader->load($filePath);

        $sheetCount = 0;
        $rowCount = 0;
        $warnings = [];

        DB::transaction(function () use (
            $batch,
            $spreadsheet,
            &$sheetCount,
            &$rowCount,
            &$warnings
        ): void {
            $batch->rows()->delete();

            foreach ($spreadsheet->getWorksheetIterator() as $worksheet) {
                if (
                    str_contains(
                        mb_strtoupper($worksheet->getTitle()),
                        'REKAPAN'
                    )
                ) {
                    continue;
                }

                $periodDate = $this->resolvePeriodDate(
                    $worksheet->getTitle(),
                    $batch->cutoff_date
                );

                if (!$periodDate) {
                    continue;
                }

                if ($periodDate->gt($batch->cutoff_date)) {
                    continue;
                }

                $headerRow = $this->findHeaderRow($worksheet);

                if (!$headerRow) {
                    $warnings[] = [
                        'message' => sprintf(
                            'Header sheet "%s" tidak ditemukan.',
                            $worksheet->getTitle()
                        ),
                    ];

                    continue;
                }

                $createdRows = $this->readWorksheet(
                    $batch,
                    $worksheet,
                    $headerRow,
                    $periodDate
                );

                if ($createdRows > 0) {
                    $sheetCount++;
                    $rowCount += $createdRows;
                }
            }

            if ($sheetCount === 0) {
                throw new RuntimeException(
                    'Tidak ada sheet kas yang dapat dibaca.'
                );
            }

            $batch->update([
                'status' => 'previewed',
                'sheet_count' => $sheetCount,
                'row_count' => $rowCount,
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
        $maxRow = min(
            $worksheet->getHighestDataRow(),
            15
        );

        for ($row = 1; $row <= $maxRow; $row++) {
            $columnA = mb_strtoupper(
                trim(
                    (string) $worksheet
                        ->getCell("A{$row}")
                        ->getValue()
                )
            );

            $columnB = mb_strtoupper(
                trim(
                    (string) $worksheet
                        ->getCell("B{$row}")
                        ->getValue()
                )
            );

            if (
                $columnA === 'TGL'
                && $columnB === 'URAIAN'
            ) {
                return $row;
            }
        }

        return null;
    }

    private function readWorksheet(
        CashImportBatch $batch,
        Worksheet $worksheet,
        int $headerRow,
        Carbon $periodDate
    ): int {
        $createdRows = 0;
        $highestRow = $worksheet->getHighestDataRow();

        for (
            $rowNumber = $headerRow + 1;
            $rowNumber <= $highestRow;
            $rowNumber++
        ) {
            $description = trim(
                (string) $worksheet
                    ->getCell("B{$rowNumber}")
                    ->getValue()
            );

            $incomeCode = mb_strtoupper(
                trim(
                    (string) $worksheet
                        ->getCell("I{$rowNumber}")
                        ->getValue()
                )
            );

            $normalizedDescription = mb_strtoupper(
                $description
            );

            if (
                str_starts_with(
                    $normalizedDescription,
                    'JUMLAH BULAN'
                )
                || $normalizedDescription === 'TOTAL'
            ) {
                break;
            }

            /*
             * Kolom kas keluar hanya dibaca ketika uraian
             * pada kolom B tersedia. Ini mencegah saldo
             * bawaan atau subtotal tanpa uraian terbaca
             * sebagai transaksi.
             */
            $expenseAllowed = $description !== '';

            $payload = [
                'cash_import_batch_id' => $batch->id,
                'sheet_name' => $worksheet->getTitle(),
                'row_number' => $rowNumber,
                'period_date' => $periodDate->toDateString(),
                'description' => $description ?: null,
                'income_code' => $incomeCode ?: null,

                'financing_expense' => $expenseAllowed
                    ? $this->cellNumber(
                        $worksheet,
                        "C{$rowNumber}"
                    )
                    : 0,

                'principal_refund' => $expenseAllowed
                    ? $this->cellNumber(
                        $worksheet,
                        "D{$rowNumber}"
                    )
                    : 0,

                'mandatory_refund' => $expenseAllowed
                    ? $this->cellNumber(
                        $worksheet,
                        "E{$rowNumber}"
                    )
                    : 0,

                'voluntary_withdrawal' => $expenseAllowed
                    ? $this->cellNumber(
                        $worksheet,
                        "F{$rowNumber}"
                    )
                    : 0,

                'transport_expense' => $expenseAllowed
                    ? $this->cellNumber(
                        $worksheet,
                        "G{$rowNumber}"
                    )
                    : 0,

                'other_expense' => $expenseAllowed
                    ? $this->cellNumber(
                        $worksheet,
                        "H{$rowNumber}"
                    )
                    : 0,

                'installment_income' => $incomeCode === 'ANG'
                    ? $this->cellNumber(
                        $worksheet,
                        "J{$rowNumber}"
                    )
                    : 0,

                'profit_share_income' => in_array(
                    $incomeCode,
                    ['BH', 'BG. HASIL', 'BAGI HASIL'],
                    true
                )
                    ? $this->cellNumber(
                        $worksheet,
                        "K{$rowNumber}"
                    )
                    : 0,

                'administration_income' => $incomeCode === 'ADM'
                    ? $this->cellNumber(
                        $worksheet,
                        "L{$rowNumber}"
                    )
                    : 0,

                'principal_deposit' => $incomeCode === 'SP'
                    ? $this->cellNumber(
                        $worksheet,
                        "M{$rowNumber}"
                    )
                    : 0,

                'mandatory_deposit' => $incomeCode === 'SW'
                    ? $this->cellNumber(
                        $worksheet,
                        "N{$rowNumber}"
                    )
                    : 0,

                'voluntary_deposit' => $incomeCode === 'SS'
                    ? $this->cellNumber(
                        $worksheet,
                        "O{$rowNumber}"
                    )
                    : 0,
            ];

            $numericFields = [
                'financing_expense',
                'principal_refund',
                'mandatory_refund',
                'voluntary_withdrawal',
                'transport_expense',
                'other_expense',
                'installment_income',
                'profit_share_income',
                'administration_income',
                'principal_deposit',
                'mandatory_deposit',
                'voluntary_deposit',
            ];

            $hasAmount = collect($numericFields)
                ->contains(
                    fn (string $field): bool =>
                        (float) $payload[$field] > 0
                );

            if (!$hasAmount) {
                continue;
            }

            $payload['raw_data'] = [
                'URAIAN' => $description,
                'KET' => $incomeCode,
                'PEMBIAYAAN' => $payload[
                    'financing_expense'
                ],
                'SP_KELUAR' => $payload[
                    'principal_refund'
                ],
                'SW_KELUAR' => $payload[
                    'mandatory_refund'
                ],
                'SS_KELUAR' => $payload[
                    'voluntary_withdrawal'
                ],
                'TRANSPORT' => $payload[
                    'transport_expense'
                ],
                'LAIN_LAIN' => $payload[
                    'other_expense'
                ],
                'ANGSURAN' => $payload[
                    'installment_income'
                ],
                'BAGI_HASIL' => $payload[
                    'profit_share_income'
                ],
                'ADMINISTRASI' => $payload[
                    'administration_income'
                ],
                'SP_MASUK' => $payload[
                    'principal_deposit'
                ],
                'SW_MASUK' => $payload[
                    'mandatory_deposit'
                ],
                'SS_MASUK' => $payload[
                    'voluntary_deposit'
                ],
            ];

            $payload['status'] = 'ready';

            CashImportRow::create($payload);
            $createdRows++;
        }

        return $createdRows;
    }

    private function resolvePeriodDate(
        string $sheetName,
        Carbon $cutoffDate
    ): ?Carbon {
        $normalizedName = mb_strtoupper(
            trim($sheetName)
        );

        $monthNumber = null;

        foreach (self::MONTHS as $month => $number) {
            if (str_contains($normalizedName, $month)) {
                $monthNumber = $number;
                break;
            }
        }

        if (!$monthNumber) {
            return null;
        }

        preg_match(
            '/\b(20\d{2})\b/',
            $normalizedName,
            $yearMatch
        );

        $year = isset($yearMatch[1])
            ? (int) $yearMatch[1]
            : $cutoffDate->year;

        return Carbon::create(
            $year,
            $monthNumber,
            1
        )->endOfMonth();
    }

    private function cellNumber(
        Worksheet $worksheet,
        string $coordinate
    ): float {
        return $this->number(
            $worksheet
                ->getCell($coordinate)
                ->getValue()
        );
    }

    private function number(mixed $value): float
    {
        if (
            $value === null
            || $value === ''
            || $value === '-'
        ) {
            return 0;
        }

        if (is_numeric($value)) {
            return round(
                (float) $value,
                2
            );
        }

        $value = str_ireplace(
            ['Rp', 'IDR', ' '],
            '',
            trim((string) $value)
        );

        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return is_numeric($value)
            ? round((float) $value, 2)
            : 0;
    }
}
