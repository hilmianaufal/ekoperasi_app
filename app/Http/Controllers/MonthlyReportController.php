<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingTransaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MonthlyReportController extends Controller
{
    private const REPORTS = [
        'installments' => 'Rekapan Angsuran',
        'profit-share' => 'Rekapan Bagi Hasil',
        'administration' => 'Rekapan Administrasi',
        'principal-savings' => 'Rekapan Simpanan Pokok',
        'mandatory-savings' => 'Rekapan Simpanan Wajib',
        'voluntary-savings' => 'Rekapan Simpanan Sukarela',
        'loans' => 'Rekapan Pinjaman',
        'transport-expenses' => 'Rekapan Pengeluaran Transportasi',
        'other-expenses' => 'Rekapan Pengeluaran Lain-lain',
        'voluntary-withdrawals' => 'Rekapan Penarikan Simpanan Sukarela',
        'mandatory-withdrawals' => 'Rekapan Penarikan Simpanan Wajib',
    ];

    public function index(Request $request): View
    {
        $filters = $this->validatedFilters($request);

        $periodStart = Carbon::create(
            $filters['year'],
            $filters['month'],
            1
        )->startOfMonth();

        $periodEnd = $periodStart
            ->copy()
            ->endOfMonth();

        $rows = $this->buildRows(
            type: $filters['type'],
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            memberId: $filters['member_id']
        );

        $summary = [
            'transaction_count' => $rows->count(),
            'principal_total' => round(
                (float) $rows->sum('principal_amount'),
                2
            ),
            'profit_share_total' => round(
                (float) $rows->sum('profit_share_amount'),
                2
            ),
            'administration_total' => round(
                (float) $rows->sum('administration_amount'),
                2
            ),
            'total_amount' => round(
                (float) $rows->sum('amount'),
                2
            ),
        ];

        $reports = self::REPORTS;

        $members = Member::query()
            ->orderBy('name')
            ->get([
                'id',
                'member_number',
                'name',
            ]);

        $paginatedRows = $this->paginateCollection(
            $rows,
            $request,
            20
        );

        return view(
            'monthly-reports.index',
            compact(
                'reports',
                'members',
                'filters',
                'periodStart',
                'periodEnd',
                'summary',
                'paginatedRows'
            )
        );
    }

    public function excel(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);

        $periodStart = Carbon::create(
            $filters['year'],
            $filters['month'],
            1
        )->startOfMonth();

        $periodEnd = $periodStart
            ->copy()
            ->endOfMonth();

        $rows = $this->buildRows(
            type: $filters['type'],
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            memberId: $filters['member_id']
        );

        $reportTitle = self::REPORTS[$filters['type']];

        $memberLabel = 'Semua Anggota';

        if ($filters['member_id']) {
            $member = Member::query()
                ->find($filters['member_id']);

            if ($member) {
                $memberLabel = sprintf(
                    '%s - %s',
                    $member->member_number,
                    $member->name
                );
            }
        }

        $fileName = sprintf(
            '%s-%04d-%02d.xlsx',
            str($filters['type'])->slug(),
            $filters['year'],
            $filters['month']
        );

        return response()->streamDownload(
            function () use (
                $rows,
                $reportTitle,
                $periodStart,
                $memberLabel
            ): void {
                $spreadsheet = new Spreadsheet();
                $sheet = $spreadsheet->getActiveSheet();
                $sheet->setTitle('Rekapan Bulanan');

                $sheet->mergeCells('A1:L1');
                $sheet->mergeCells('A2:L2');
                $sheet->mergeCells('A3:L3');

                $sheet->setCellValue('A1', $reportTitle);
                $sheet->setCellValue(
                    'A2',
                    'Periode ' . $periodStart->translatedFormat('F Y')
                );
                $sheet->setCellValue(
                    'A3',
                    'Anggota: ' . $memberLabel
                );

                $sheet->getStyle('A1:L1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 18,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '047857'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getStyle('A2:L3')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => '334155'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'ECFDF5'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);

                $sheet->getRowDimension(1)->setRowHeight(30);
                $sheet->getRowDimension(2)->setRowHeight(22);
                $sheet->getRowDimension(3)->setRowHeight(22);

                $headings = [
                    'No',
                    'Tanggal',
                    'Kode Transaksi',
                    'Nomor Anggota',
                    'Nama Anggota',
                    'Referensi',
                    'Keterangan',
                    'Pokok',
                    'Bagi Hasil',
                    'Administrasi',
                    'Nominal',
                    'Petugas',
                ];

                $headerRow = 5;
                $sheet->fromArray(
                    $headings,
                    null,
                    "A{$headerRow}"
                );

                $sheet->getStyle("A{$headerRow}:L{$headerRow}")
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => 'FFFFFF'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => '0F766E'],
                        ],
                        'alignment' => [
                            'horizontal' => Alignment::HORIZONTAL_CENTER,
                            'vertical' => Alignment::VERTICAL_CENTER,
                            'wrapText' => true,
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => 'CBD5E1'],
                            ],
                        ],
                    ]);

                $sheet->getRowDimension($headerRow)->setRowHeight(30);

                $dataRow = 6;

                foreach ($rows->values() as $index => $row) {
                    $date = $row['date'] instanceof Carbon
                        ? $row['date']
                        : Carbon::parse($row['date']);

                    $sheet->setCellValue(
                        "A{$dataRow}",
                        $index + 1
                    );
                    $sheet->setCellValue(
                        "B{$dataRow}",
                        $date->format('d-m-Y')
                    );
                    $sheet->setCellValueExplicit(
                        "C{$dataRow}",
                        (string) ($row['code'] ?? '-'),
                        DataType::TYPE_STRING
                    );
                    $sheet->setCellValueExplicit(
                        "D{$dataRow}",
                        (string) ($row['member_number'] ?? '-'),
                        DataType::TYPE_STRING
                    );
                    $sheet->setCellValue(
                        "E{$dataRow}",
                        $row['member_name'] ?? '-'
                    );
                    $sheet->setCellValueExplicit(
                        "F{$dataRow}",
                        (string) ($row['reference'] ?? '-'),
                        DataType::TYPE_STRING
                    );
                    $sheet->setCellValue(
                        "G{$dataRow}",
                        $row['description'] ?? '-'
                    );
                    $sheet->setCellValue(
                        "H{$dataRow}",
                        (float) ($row['principal_amount'] ?? 0)
                    );
                    $sheet->setCellValue(
                        "I{$dataRow}",
                        (float) ($row['profit_share_amount'] ?? 0)
                    );
                    $sheet->setCellValue(
                        "J{$dataRow}",
                        (float) ($row['administration_amount'] ?? 0)
                    );
                    $sheet->setCellValue(
                        "K{$dataRow}",
                        (float) ($row['amount'] ?? 0)
                    );
                    $sheet->setCellValue(
                        "L{$dataRow}",
                        $row['user_name'] ?? '-'
                    );

                    $dataRow++;
                }

                $lastDataRow = max($dataRow - 1, $headerRow);
                $totalRow = $dataRow;

                if ($rows->isNotEmpty()) {
                    $sheet->getStyle("A6:L{$lastDataRow}")
                        ->applyFromArray([
                            'alignment' => [
                                'vertical' => Alignment::VERTICAL_TOP,
                                'wrapText' => true,
                            ],
                            'borders' => [
                                'allBorders' => [
                                    'borderStyle' => Border::BORDER_THIN,
                                    'color' => ['rgb' => 'E2E8F0'],
                                ],
                            ],
                        ]);

                    $sheet->getStyle("A6:B{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_CENTER
                        );

                    $sheet->getStyle("H6:K{$lastDataRow}")
                        ->getNumberFormat()
                        ->setFormatCode('"Rp" #,##0');

                    $sheet->getStyle("H6:K{$lastDataRow}")
                        ->getAlignment()
                        ->setHorizontal(
                            Alignment::HORIZONTAL_RIGHT
                        );
                }

                $sheet->setCellValue("G{$totalRow}", 'TOTAL');
                $sheet->setCellValue(
                    "H{$totalRow}",
                    round(
                        (float) $rows->sum('principal_amount'),
                        2
                    )
                );
                $sheet->setCellValue(
                    "I{$totalRow}",
                    round(
                        (float) $rows->sum('profit_share_amount'),
                        2
                    )
                );
                $sheet->setCellValue(
                    "J{$totalRow}",
                    round(
                        (float) $rows->sum('administration_amount'),
                        2
                    )
                );
                $sheet->setCellValue(
                    "K{$totalRow}",
                    round(
                        (float) $rows->sum('amount'),
                        2
                    )
                );

                $sheet->getStyle("A{$totalRow}:L{$totalRow}")
                    ->applyFromArray([
                        'font' => [
                            'bold' => true,
                            'color' => ['rgb' => '065F46'],
                        ],
                        'fill' => [
                            'fillType' => Fill::FILL_SOLID,
                            'startColor' => ['rgb' => 'D1FAE5'],
                        ],
                        'borders' => [
                            'allBorders' => [
                                'borderStyle' => Border::BORDER_THIN,
                                'color' => ['rgb' => '6EE7B7'],
                            ],
                        ],
                    ]);

                $sheet->getStyle("H{$totalRow}:K{$totalRow}")
                    ->getNumberFormat()
                    ->setFormatCode('"Rp" #,##0');

                $columnWidths = [
                    'A' => 7,
                    'B' => 14,
                    'C' => 23,
                    'D' => 18,
                    'E' => 27,
                    'F' => 23,
                    'G' => 40,
                    'H' => 18,
                    'I' => 18,
                    'J' => 18,
                    'K' => 18,
                    'L' => 23,
                ];

                foreach ($columnWidths as $column => $width) {
                    $sheet->getColumnDimension($column)
                        ->setWidth($width);
                }

                $sheet->freezePane('A6');
                $sheet->setAutoFilter(
                    "A{$headerRow}:L{$lastDataRow}"
                );

                $sheet->getPageSetup()
                    ->setOrientation(
                        PageSetup::ORIENTATION_LANDSCAPE
                    );
                $sheet->getPageSetup()->setFitToWidth(1);
                $sheet->getPageSetup()->setFitToHeight(0);

                $sheet->getPageMargins()->setTop(0.5);
                $sheet->getPageMargins()->setBottom(0.5);
                $sheet->getPageMargins()->setLeft(0.4);
                $sheet->getPageMargins()->setRight(0.4);

                $writer = new Xlsx($spreadsheet);
                $writer->save('php://output');

                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            },
            $fileName,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
                'Pragma' => 'public',
            ]
        );
    }

    private function validatedFilters(Request $request): array
    {
        $currentYear = (int) now()->year;

        $data = $request->validate([
            'type' => [
                'nullable',
                Rule::in(array_keys(self::REPORTS)),
            ],
            'month' => [
                'nullable',
                'integer',
                'between:1,12',
            ],
            'year' => [
                'nullable',
                'integer',
                'between:2000,' . ($currentYear + 1),
            ],
            'member_id' => [
                'nullable',
                'integer',
                Rule::exists('members', 'id'),
            ],
        ], [
            'type.in' => 'Jenis rekapan tidak valid.',
            'month.between' => 'Bulan laporan tidak valid.',
            'year.between' => 'Tahun laporan tidak valid.',
            'member_id.exists' => 'Anggota yang dipilih tidak ditemukan.',
        ]);

        return [
            'type' => $data['type'] ?? 'installments',
            'month' => (int) ($data['month'] ?? now()->month),
            'year' => (int) ($data['year'] ?? now()->year),
            'member_id' => isset($data['member_id'])
                ? (int) $data['member_id']
                : null,
        ];
    }

    private function buildRows(
        string $type,
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $memberId
    ): Collection {
        $rows = match ($type) {
            'installments' => $this->installmentRows(
                $periodStart,
                $periodEnd,
                $memberId
            ),

            'profit-share' => $this->profitShareRows(
                $periodStart,
                $periodEnd,
                $memberId
            ),

            'administration' => $this->administrationRows(
                $periodStart,
                $periodEnd,
                $memberId
            ),

            'principal-savings' => $this->savingRows(
                $periodStart,
                $periodEnd,
                $memberId,
                'POKOK',
                'deposit'
            ),

            'mandatory-savings' => $this->savingRows(
                $periodStart,
                $periodEnd,
                $memberId,
                'WAJIB',
                'deposit'
            ),

            'voluntary-savings' => $this->savingRows(
                $periodStart,
                $periodEnd,
                $memberId,
                'SUKARELA',
                'deposit'
            ),

            'loans' => $this->loanRows(
                $periodStart,
                $periodEnd,
                $memberId
            ),

            'transport-expenses' => $this->expenseRows(
                $periodStart,
                $periodEnd,
                'Transportasi'
            ),

            'other-expenses' => $this->expenseRows(
                $periodStart,
                $periodEnd,
                'Lainnya'
            ),

            'voluntary-withdrawals' => $this->savingRows(
                $periodStart,
                $periodEnd,
                $memberId,
                'SUKARELA',
                'withdrawal'
            ),

            'mandatory-withdrawals' => $this->savingRows(
                $periodStart,
                $periodEnd,
                $memberId,
                'WAJIB',
                'withdrawal'
            ),

            default => collect(),
        };

        return $rows
            ->sortByDesc(function (array $row): string {
                $timestamp = $row['date'] instanceof Carbon
                    ? $row['date']->timestamp
                    : Carbon::parse($row['date'])->timestamp;

                return sprintf(
                    '%012d-%012d',
                    $timestamp,
                    (int) ($row['id'] ?? 0)
                );
            })
            ->values();
    }

    private function installmentRows(
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $memberId
    ): Collection {
        return InstallmentPayment::query()
            ->with([
                'installment.loan.member:id,member_number,name',
                'user:id,name',
            ])
            ->whereBetween('payment_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->when(
                $memberId,
                fn ($query) => $query->whereHas(
                    'installment.loan',
                    fn ($loanQuery) => $loanQuery->where(
                        'member_id',
                        $memberId
                    )
                )
            )
            ->get()
            ->map(function (InstallmentPayment $payment): array {
                $allocation = $this->paymentAllocation($payment);
                $installment = $payment->installment;
                $loan = $installment->loan;
                $member = $loan->member;

                return $this->row([
                    'id' => $payment->id,
                    'date' => $payment->payment_date,
                    'code' => $payment->payment_code,
                    'member_number' => $member?->member_number,
                    'member_name' => $member?->name,
                    'reference' => $loan->loan_number,
                    'description' => sprintf(
                        'Angsuran ke-%d',
                        $installment->installment_number
                    ),
                    'principal_amount' => $allocation['principal'],
                    'profit_share_amount' => $allocation['profit_share'],
                    'administration_amount' => 0,
                    'amount' => (float) $payment->amount,
                    'user_name' => $payment->user?->name,
                ]);
            });
    }

    private function profitShareRows(
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $memberId
    ): Collection {
        return $this->installmentRows(
            $periodStart,
            $periodEnd,
            $memberId
        )
            ->filter(
                fn (array $row): bool =>
                    (float) $row['profit_share_amount'] > 0
            )
            ->map(function (array $row): array {
                $row['principal_amount'] = 0;
                $row['amount'] = $row['profit_share_amount'];
                $row['description'] = 'Pendapatan bagi hasil';

                return $row;
            })
            ->values();
    }

    private function administrationRows(
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $memberId
    ): Collection {
        $loanAdministration = Loan::query()
            ->with([
                'member:id,member_number,name',
                'approver:id,name',
            ])
            ->whereNotNull('administration_collected_at')
            ->whereBetween('administration_collected_at', [
                $periodStart->copy()->startOfDay(),
                $periodEnd->copy()->endOfDay(),
            ])
            ->where('administration_fee', '>', 0)
            ->when(
                $memberId,
                fn ($query) => $query->where(
                    'member_id',
                    $memberId
                )
            )
            ->get()
            ->map(function (Loan $loan): array {
                return $this->row([
                    'id' => $loan->id,
                    'date' => $loan->administration_collected_at,
                    'code' => $loan->loan_number,
                    'member_number' => $loan->member?->member_number,
                    'member_name' => $loan->member?->name,
                    'reference' => $loan->loan_number,
                    'description' => sprintf(
                        'Administrasi pinjaman (%s)',
                        $loan->administration_collection_method_label
                    ),
                    'principal_amount' => 0,
                    'profit_share_amount' => 0,
                    'administration_amount' => (float) $loan->administration_fee,
                    'amount' => (float) $loan->administration_fee,
                    'user_name' => $loan->approver?->name,
                ]);
            });

        /*
         * Menjaga rekapan administrasi lama hasil import
         * yang sebelumnya tersimpan pada pembayaran angsuran.
         */
        $legacyAdministration = InstallmentPayment::query()
            ->with([
                'installment.loan.member:id,member_number,name',
                'user:id,name',
            ])
            ->whereBetween('payment_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->where('administration_fee', '>', 0)
            ->when(
                $memberId,
                fn ($query) => $query->whereHas(
                    'installment.loan',
                    fn ($loanQuery) => $loanQuery->where(
                        'member_id',
                        $memberId
                    )
                )
            )
            ->get()
            ->map(function (InstallmentPayment $payment): array {
                $loan = $payment->installment->loan;
                $member = $loan->member;

                return $this->row([
                    'id' => $payment->id,
                    'date' => $payment->payment_date,
                    'code' => $payment->payment_code,
                    'member_number' => $member?->member_number,
                    'member_name' => $member?->name,
                    'reference' => $loan->loan_number,
                    'description' => 'Administrasi data lama/import',
                    'principal_amount' => 0,
                    'profit_share_amount' => 0,
                    'administration_amount' => (float) $payment->administration_fee,
                    'amount' => (float) $payment->administration_fee,
                    'user_name' => $payment->user?->name,
                ]);
            });

        return $loanAdministration
            ->concat($legacyAdministration)
            ->values();
    }

    private function savingRows(
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $memberId,
        string $savingCode,
        string $transactionType
    ): Collection {
        return SavingTransaction::query()
            ->with([
                'member:id,member_number,name',
                'savingType:id,code,name',
                'user:id,name',
            ])
            ->whereBetween('transaction_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->where('transaction_type', $transactionType)
            ->whereHas(
                'savingType',
                fn ($query) => $query->where(
                    'code',
                    $savingCode
                )
            )
            ->when(
                $memberId,
                fn ($query) => $query->where(
                    'member_id',
                    $memberId
                )
            )
            ->get()
            ->map(function (SavingTransaction $transaction): array {
                return $this->row([
                    'id' => $transaction->id,
                    'date' => $transaction->transaction_date,
                    'code' => $transaction->transaction_code,
                    'member_number' => $transaction->member?->member_number,
                    'member_name' => $transaction->member?->name,
                    'reference' => $transaction->savingType?->name,
                    'description' => $transaction->notes
                        ?: sprintf(
                            '%s %s',
                            $transaction->transaction_type_label,
                            $transaction->savingType?->name
                        ),
                    'principal_amount' => 0,
                    'profit_share_amount' => 0,
                    'administration_amount' => 0,
                    'amount' => (float) $transaction->amount,
                    'user_name' => $transaction->user?->name,
                ]);
            });
    }

    private function loanRows(
        Carbon $periodStart,
        Carbon $periodEnd,
        ?int $memberId
    ): Collection {
        return Loan::query()
            ->with([
                'member:id,member_number,name',
                'approver:id,name',
            ])
            ->whereIn('status', [
                'active',
                'paid',
            ])
            ->whereBetween('start_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->when(
                $memberId,
                fn ($query) => $query->where(
                    'member_id',
                    $memberId
                )
            )
            ->get()
            ->map(function (Loan $loan): array {
                $amount = (bool) $loan->is_legacy
                    ? (float) $loan->disbursed_during_import
                    : (float) $loan->principal_amount;

                return $this->row([
                    'id' => $loan->id,
                    'date' => $loan->start_date,
                    'code' => $loan->loan_number,
                    'member_number' => $loan->member?->member_number,
                    'member_name' => $loan->member?->name,
                    'reference' => sprintf(
                        '%d bulan · %s%%',
                        (int) $loan->tenor_months,
                        number_format(
                            (float) $loan->interest_rate,
                            2,
                            ',',
                            '.'
                        )
                    ),
                    'description' => $loan->purpose,
                    'principal_amount' => $amount,
                    'profit_share_amount' => 0,
                    'administration_amount' => 0,
                    'amount' => $amount,
                    'user_name' => $loan->approver?->name,
                ]);
            })
            ->filter(
                fn (array $row): bool =>
                    (float) $row['amount'] > 0
            )
            ->values();
    }

    private function expenseRows(
        Carbon $periodStart,
        Carbon $periodEnd,
        string $category
    ): Collection {
        return CashTransaction::query()
            ->with('user:id,name')
            ->where('direction', 'expense')
            ->where('category', $category)
            ->whereBetween('transaction_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ])
            ->get()
            ->map(function (CashTransaction $transaction): array {
                return $this->row([
                    'id' => $transaction->id,
                    'date' => $transaction->transaction_date,
                    'code' => $transaction->transaction_code,
                    'member_number' => '-',
                    'member_name' => '-',
                    'reference' => $transaction->payment_method_label,
                    'description' => $transaction->description,
                    'principal_amount' => 0,
                    'profit_share_amount' => 0,
                    'administration_amount' => 0,
                    'amount' => (float) $transaction->amount,
                    'user_name' => $transaction->user?->name,
                ]);
            });
    }

    private function paymentAllocation(
        InstallmentPayment $payment
    ): array {
        $amount = round(
            (float) $payment->amount,
            2
        );

        $principal = round(
            (float) ($payment->principal_amount ?? 0),
            2
        );

        $profitShare = round(
            (float) ($payment->profit_share_amount ?? 0),
            2
        );

        $administration = round(
            (float) ($payment->administration_fee ?? 0),
            2
        );

        if ($principal <= 0 && $profitShare <= 0) {
            $availableAmount = max(
                round(
                    $amount - $administration,
                    2
                ),
                0
            );

            $principal = min(
                $availableAmount,
                round(
                    (float) $payment->installment->principal_amount,
                    2
                )
            );

            $profitShare = max(
                round(
                    $availableAmount - $principal,
                    2
                ),
                0
            );
        }

        return [
            'principal' => $principal,
            'profit_share' => $profitShare,
        ];
    }

    private function row(array $data): array
    {
        return [
            'id' => (int) ($data['id'] ?? 0),
            'date' => $data['date'],
            'code' => $data['code'] ?: '-',
            'member_number' => $data['member_number'] ?: '-',
            'member_name' => $data['member_name'] ?: '-',
            'reference' => $data['reference'] ?: '-',
            'description' => $data['description'] ?: '-',
            'principal_amount' => round(
                (float) ($data['principal_amount'] ?? 0),
                2
            ),
            'profit_share_amount' => round(
                (float) ($data['profit_share_amount'] ?? 0),
                2
            ),
            'administration_amount' => round(
                (float) ($data['administration_amount'] ?? 0),
                2
            ),
            'amount' => round(
                (float) ($data['amount'] ?? 0),
                2
            ),
            'user_name' => $data['user_name'] ?: '-',
        ];
    }

    private function paginateCollection(
        Collection $items,
        Request $request,
        int $perPage
    ): LengthAwarePaginator {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return new LengthAwarePaginator(
            $items
                ->forPage($page, $perPage)
                ->values(),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }
}
