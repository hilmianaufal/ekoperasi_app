<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\ShuPayment;
use App\Models\ShuPeriod;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShuReportController extends Controller
{
    public function show(
        ShuPeriod $shuPeriod
    ): View {
        $report = $this->buildReport(
            $shuPeriod
        );

        return view(
            'shu-reports.show',
            [
                ...$report,
                'printMode' => false,
            ]
        );
    }

    public function print(
        ShuPeriod $shuPeriod
    ): View {
        $report = $this->buildReport(
            $shuPeriod
        );

        return view(
            'shu-reports.show',
            [
                ...$report,
                'printMode' => true,
            ]
        );
    }

    public function export(
        ShuPeriod $shuPeriod
    ): StreamedResponse {
        $report = $this->buildReport(
            $shuPeriod
        );

        $period = $report['shuPeriod'];
        $allocations = $report['allocations'];
        $summary = $report['summary'];

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle(
            'SHU ' . $period->year
        );

        $sheet->mergeCells('A1:N1');
        $sheet->setCellValue(
            'A1',
            'LAPORAN PEMBAGIAN SISA HASIL USAHA'
        );

        $sheet->mergeCells('A2:N2');
        $sheet->setCellValue(
            'A2',
            sprintf(
                'PERIODE TAHUN %d',
                $period->year
            )
        );

        $sheet->setCellValue(
            'A4',
            'Ketetapan SHU Anggota'
        );

        $sheet->setCellValue(
            'B4',
            $summary['declared_member_shu']
        );

        $sheet->setCellValue(
            'D4',
            'Total Alokasi'
        );

        $sheet->setCellValue(
            'E4',
            $summary['allocated_total']
        );

        $sheet->setCellValue(
            'G4',
            'Selisih Alokasi'
        );

        $sheet->setCellValue(
            'H4',
            $summary['difference']
        );

        $sheet->setCellValue(
            'J4',
            'Status Periode'
        );

        $sheet->setCellValue(
            'K4',
            $period->status_label
        );

        $sheet->setCellValue(
            'A5',
            'Total JASUS'
        );

        $sheet->setCellValue(
            'B5',
            $summary['business_service']
        );

        $sheet->setCellValue(
            'D5',
            'Total JASIM'
        );

        $sheet->setCellValue(
            'E5',
            $summary['saving_service']
        );

        $sheet->setCellValue(
            'G5',
            'Sudah Dibayar'
        );

        $sheet->setCellValue(
            'H5',
            $summary['paid_total']
        );

        $sheet->setCellValue(
            'J5',
            'Sisa Pembayaran'
        );

        $sheet->setCellValue(
            'K5',
            $summary['remaining_total']
        );

        $sheet->setCellValue(
            'A6',
            'Jumlah Anggota'
        );

        $sheet->setCellValue(
            'B6',
            $summary['member_count']
        );

        $sheet->setCellValue(
            'D6',
            'Anggota Lunas'
        );

        $sheet->setCellValue(
            'E6',
            $summary['paid_count']
        );

        $sheet->setCellValue(
            'G6',
            'Belum Lunas'
        );

        $sheet->setCellValue(
            'H6',
            $summary['unpaid_count']
        );

        $sheet->setCellValue(
            'J6',
            'Kas Keluar SHU'
        );

        $sheet->setCellValue(
            'K6',
            $summary['cash_expense_total']
        );

        $headers = [
            'No.',
            'Nomor Anggota',
            'Nama Anggota',
            'Saldo Piutang',
            'Bagi Hasil',
            'Simpanan Pokok',
            'Simpanan Wajib',
            'Jumlah Simpanan',
            'JASUS',
            'JASIM',
            'Total SHU',
            'Sudah Dibayar',
            'Sisa',
            'Status',
        ];

        $headerRow = 8;

        $sheet->fromArray(
            $headers,
            null,
            "A{$headerRow}"
        );

        $dataRow = $headerRow + 1;

        foreach (
            $allocations as $index => $allocation
        ) {
            $remaining = max(
                (float) $allocation->total_shu
                - (float) $allocation->paid_amount,
                0
            );

            $status = match (
                $allocation->payment_status
            ) {
                'paid' => 'Lunas',
                'partial' => 'Sebagian',
                default => 'Belum Dibayar',
            };

            $sheet->fromArray(
                [
                    $index + 1,
                    $allocation->member
                        ?->member_number,
                    $allocation->member
                        ?->name,
                    (float) $allocation
                        ->receivable_balance,
                    (float) $allocation
                        ->profit_share_base,
                    (float) $allocation
                        ->principal_saving,
                    (float) $allocation
                        ->mandatory_saving,
                    (float) $allocation
                        ->saving_balance,
                    (float) $allocation
                        ->business_service_amount,
                    (float) $allocation
                        ->saving_service_amount,
                    (float) $allocation
                        ->total_shu,
                    (float) $allocation
                        ->paid_amount,
                    $remaining,
                    $status,
                ],
                null,
                "A{$dataRow}"
            );

            $dataRow++;
        }

        $totalRow = $dataRow;

        $sheet->mergeCells(
            "A{$totalRow}:C{$totalRow}"
        );

        $sheet->setCellValue(
            "A{$totalRow}",
            'TOTAL'
        );

        $totals = [
            'D' => $summary['receivable_balance'],
            'E' => $summary['profit_share_base'],
            'F' => $summary['principal_saving'],
            'G' => $summary['mandatory_saving'],
            'H' => $summary['saving_balance'],
            'I' => $summary['business_service'],
            'J' => $summary['saving_service'],
            'K' => $summary['allocated_total'],
            'L' => $summary['paid_total'],
            'M' => $summary['remaining_total'],
        ];

        foreach (
            $totals as $column => $value
        ) {
            $sheet->setCellValue(
                "{$column}{$totalRow}",
                $value
            );
        }

        $sheet->setCellValue(
            "N{$totalRow}",
            ''
        );

        $sheet->getStyle(
            'A1:N1'
        )->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->getStyle(
            'A2:N2'
        )->getFont()
            ->setBold(true)
            ->setSize(12);

        $sheet->getStyle(
            'A1:N2'
        )->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        $sheet->getStyle(
            "A{$headerRow}:N{$headerRow}"
        )->getFont()
            ->setBold(true)
            ->getColor()
            ->setARGB('FFFFFFFF');

        $sheet->getStyle(
            "A{$headerRow}:N{$headerRow}"
        )->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FF047857');

        $sheet->getStyle(
            "A{$headerRow}:N{$headerRow}"
        )->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            )
            ->setVertical(
                Alignment::VERTICAL_CENTER
            );

        $sheet->getStyle(
            "A{$totalRow}:N{$totalRow}"
        )->getFont()
            ->setBold(true);

        $sheet->getStyle(
            "A{$totalRow}:N{$totalRow}"
        )->getFill()
            ->setFillType(
                Fill::FILL_SOLID
            )
            ->getStartColor()
            ->setARGB('FFD1FAE5');

        $sheet->getStyle(
            "A{$headerRow}:N{$totalRow}"
        )->getBorders()
            ->getAllBorders()
            ->setBorderStyle(
                Border::BORDER_THIN
            );

        $sheet->getStyle(
            "D4:H6"
        )->getNumberFormat()
            ->setFormatCode(
                '#,##0'
            );

        $sheet->getStyle(
            "K4:K6"
        )->getNumberFormat()
            ->setFormatCode(
                '#,##0'
            );

        if ($totalRow > $headerRow + 1) {
            $sheet->getStyle(
                'D9:M' . $totalRow
            )->getNumberFormat()
                ->setFormatCode(
                    '#,##0'
                );
        }

        $sheet->getColumnDimension('A')
            ->setWidth(7);

        $sheet->getColumnDimension('B')
            ->setWidth(18);

        $sheet->getColumnDimension('C')
            ->setWidth(30);

        foreach (
            range('D', 'M')
            as $column
        ) {
            $sheet->getColumnDimension(
                $column
            )->setWidth(18);
        }

        $sheet->getColumnDimension('N')
            ->setWidth(18);

        $sheet->freezePane(
            'A9'
        );

        $sheet->setAutoFilter(
            "A{$headerRow}:N"
            . max(
                $totalRow - 1,
                $headerRow
            )
        );

        $filename = sprintf(
            'laporan-shu-%d.xlsx',
            $period->year
        );

        return response()->streamDownload(
            function () use (
                $spreadsheet
            ): void {
                $writer = new Xlsx(
                    $spreadsheet
                );

                $writer->save(
                    'php://output'
                );

                $spreadsheet
                    ->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }

    private function buildReport(
        ShuPeriod $shuPeriod
    ): array {
        $shuPeriod->load([
            'approver:id,name',
        ]);

        $allocations = $shuPeriod
            ->allocations()
            ->with([
                'member:id,member_number,name',
            ])
            ->orderBy('source_number')
            ->orderBy('id')
            ->get();

        $paymentIds = ShuPayment::query()
            ->whereHas(
                'allocation',
                fn ($query) =>
                    $query->where(
                        'shu_period_id',
                        $shuPeriod->id
                    )
            )
            ->pluck('id');

        $cashExpenseTotal = $paymentIds
            ->isEmpty()
                ? 0
                : (float) CashTransaction::query()
                    ->where(
                        'source_type',
                        'shu_payment'
                    )
                    ->whereIn(
                        'source_id',
                        $paymentIds
                    )
                    ->sum('amount');

        $allocatedTotal = (float)
            $allocations->sum('total_shu');

        $paidTotal = (float)
            $allocations->sum('paid_amount');

        $summary = [
            'declared_total_shu' =>
                (float) $shuPeriod
                    ->declared_total_shu,

            'declared_member_shu' =>
                (float) $shuPeriod
                    ->declared_member_shu,

            'member_count' =>
                $allocations->count(),

            'business_service' =>
                (float) $allocations
                    ->sum(
                        'business_service_amount'
                    ),

            'saving_service' =>
                (float) $allocations
                    ->sum(
                        'saving_service_amount'
                    ),

            'receivable_balance' =>
                (float) $allocations
                    ->sum(
                        'receivable_balance'
                    ),

            'profit_share_base' =>
                (float) $allocations
                    ->sum(
                        'profit_share_base'
                    ),

            'principal_saving' =>
                (float) $allocations
                    ->sum(
                        'principal_saving'
                    ),

            'mandatory_saving' =>
                (float) $allocations
                    ->sum(
                        'mandatory_saving'
                    ),

            'saving_balance' =>
                (float) $allocations
                    ->sum(
                        'saving_balance'
                    ),

            'allocated_total' =>
                $allocatedTotal,

            'paid_total' =>
                $paidTotal,

            'remaining_total' => max(
                $allocatedTotal - $paidTotal,
                0
            ),

            'difference' => round(
                (float) $shuPeriod
                    ->declared_member_shu
                - $allocatedTotal,
                2
            ),

            'paid_count' =>
                $allocations
                    ->where(
                        'payment_status',
                        'paid'
                    )
                    ->count(),

            'partial_count' =>
                $allocations
                    ->where(
                        'payment_status',
                        'partial'
                    )
                    ->count(),

            'unpaid_count' =>
                $allocations
                    ->where(
                        'payment_status',
                        '!=',
                        'paid'
                    )
                    ->count(),

            'cash_expense_total' =>
                $cashExpenseTotal,

            'cash_difference' => round(
                $cashExpenseTotal
                - $paidTotal,
                2
            ),

            'payment_percentage' =>
                $allocatedTotal > 0
                    ? round(
                        (
                            $paidTotal
                            / $allocatedTotal
                        ) * 100,
                        2
                    )
                    : 0,
        ];

        return compact(
            'shuPeriod',
            'allocations',
            'summary'
        );
    }
}
