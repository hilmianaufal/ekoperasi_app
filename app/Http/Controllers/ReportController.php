<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    private const REPORT_TYPES = [
        'members' => 'Laporan Anggota',
        'savings' => 'Laporan Simpanan',
        'loans' => 'Laporan Pinjaman',
        'installments' => 'Laporan Pembayaran Angsuran',
        'cash' => 'Laporan Kas Koperasi',
    ];

    public function index(Request $request): View
    {
        return view(
            'reports.index',
            $this->buildReportData($request, true)
        );
    }

    public function print(Request $request): View
    {
        return view(
            'reports.print',
            $this->buildReportData($request, false)
        );
    }

    public function export(Request $request): StreamedResponse
    {
        $reportData = $this->buildReportData($request, false);
        $reportType = $reportData['reportType'];
        $rows = $reportData['rows'];

        $fileName = sprintf(
            'laporan-%s-%s.csv',
            $reportType,
            now()->format('Y-m-d-His')
        );

        return response()->streamDownload(
            function () use ($reportType, $rows): void {
                $handle = fopen('php://output', 'w');

                /*
                 * UTF-8 BOM agar karakter Indonesia terbaca
                 * dengan baik ketika dibuka di Microsoft Excel.
                 */
                fwrite($handle, "\xEF\xBB\xBF");

                fputcsv(
                    $handle,
                    $this->csvHeaders($reportType),
                    ';'
                );

                foreach ($rows as $row) {
                    fputcsv(
                        $handle,
                        $this->csvRow($reportType, $row),
                        ';'
                    );
                }

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]
        );
    }

    private function buildReportData(
        Request $request,
        bool $paginate
    ): array {
        $validated = $request->validate([
            'report_type' => [
                'nullable',
                'in:members,savings,loans,installments,cash',
            ],
            'date_from' => [
                'nullable',
                'date',
            ],
            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],
            'search' => [
                'nullable',
                'string',
                'max:150',
            ],
            'status' => [
                'nullable',
                'string',
                'max:50',
            ],
        ], [
            'date_to.after_or_equal' => 'Tanggal selesai tidak boleh lebih kecil dari tanggal mulai.',
        ]);

        $reportType = $validated['report_type'] ?? 'cash';

        $dateFrom = $validated['date_from']
            ?? now()->startOfMonth()->format('Y-m-d');

        $dateTo = $validated['date_to']
            ?? now()->format('Y-m-d');

        $search = trim(
            (string) ($validated['search'] ?? '')
        );

        $status = $validated['status'] ?? null;

        $result = match ($reportType) {
            'members' => $this->memberReport(
                $dateFrom,
                $dateTo,
                $search,
                $status,
                $paginate
            ),

            'savings' => $this->savingReport(
                $dateFrom,
                $dateTo,
                $search,
                $status,
                $paginate
            ),

            'loans' => $this->loanReport(
                $dateFrom,
                $dateTo,
                $search,
                $status,
                $paginate
            ),

            'installments' => $this->installmentReport(
                $dateFrom,
                $dateTo,
                $search,
                $status,
                $paginate
            ),

            default => $this->cashReport(
                $dateFrom,
                $dateTo,
                $search,
                $status,
                $paginate
            ),
        };

        return [
            ...$result,
            'reportTypes' => self::REPORT_TYPES,
            'reportType' => $reportType,
            'reportTitle' => self::REPORT_TYPES[$reportType],
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'search' => $search,
            'status' => $status,
        ];
    }

    private function memberReport(
        string $dateFrom,
        string $dateTo,
        string $search,
        ?string $status,
        bool $paginate
    ): array {
        $query = Member::query()
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('member_number', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                in_array($status, ['active', 'inactive'], true),
                fn (Builder $query) => $query->where('status', $status)
            )
            ->whereDate('join_date', '>=', $dateFrom)
            ->whereDate('join_date', '<=', $dateTo);

        $summaryCards = [
            [
                'label' => 'Total Anggota',
                'value' => (clone $query)->count(),
                'format' => 'number',
                'icon' => 'users',
                'class' => 'bg-blue-100 text-blue-600',
            ],
            [
                'label' => 'Anggota Aktif',
                'value' => (clone $query)
                    ->where('status', 'active')
                    ->count(),
                'format' => 'number',
                'icon' => 'user-check',
                'class' => 'bg-emerald-100 text-emerald-600',
            ],
            [
                'label' => 'Tidak Aktif',
                'value' => (clone $query)
                    ->where('status', 'inactive')
                    ->count(),
                'format' => 'number',
                'icon' => 'user-x',
                'class' => 'bg-slate-100 text-slate-600',
            ],
        ];

        $query
            ->latest('join_date')
            ->latest('id');

        return [
            'rows' => $this->finishQuery($query, $paginate),
            'summaryCards' => $summaryCards,
            'statusLabel' => 'Status Anggota',
            'statusOptions' => [
                'active' => 'Aktif',
                'inactive' => 'Tidak Aktif',
            ],
        ];
    }

    private function savingReport(
        string $dateFrom,
        string $dateTo,
        string $search,
        ?string $status,
        bool $paginate
    ): array {
        $query = SavingTransaction::query()
            ->with([
                'member:id,member_number,name',
                'savingType:id,name,code',
                'user:id,name',
            ])
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('transaction_code', 'like', "%{$search}%")
                        ->orWhereHas(
                            'member',
                            function (Builder $memberQuery) use ($search): void {
                                $memberQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere(
                                        'member_number',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                });
            })
            ->when(
                in_array($status, ['deposit', 'withdrawal'], true),
                fn (Builder $query) => $query->where(
                    'transaction_type',
                    $status
                )
            )
            ->whereDate('transaction_date', '>=', $dateFrom)
            ->whereDate('transaction_date', '<=', $dateTo);

        $totalDeposits = (float) (clone $query)
            ->where('transaction_type', 'deposit')
            ->sum('amount');

        $totalWithdrawals = (float) (clone $query)
            ->where('transaction_type', 'withdrawal')
            ->sum('amount');

        $summaryCards = [
            [
                'label' => 'Total Setoran',
                'value' => $totalDeposits,
                'format' => 'currency',
                'icon' => 'arrow-down-to-line',
                'class' => 'bg-emerald-100 text-emerald-600',
            ],
            [
                'label' => 'Total Penarikan',
                'value' => $totalWithdrawals,
                'format' => 'currency',
                'icon' => 'arrow-up-from-line',
                'class' => 'bg-red-100 text-red-600',
            ],
            [
                'label' => 'Saldo Simpanan',
                'value' => $totalDeposits - $totalWithdrawals,
                'format' => 'currency',
                'icon' => 'wallet-cards',
                'class' => 'bg-blue-100 text-blue-600',
            ],
            [
                'label' => 'Jumlah Transaksi',
                'value' => (clone $query)->count(),
                'format' => 'number',
                'icon' => 'receipt-text',
                'class' => 'bg-violet-100 text-violet-600',
            ],
        ];

        $query
            ->latest('transaction_date')
            ->latest('id');

        return [
            'rows' => $this->finishQuery($query, $paginate),
            'summaryCards' => $summaryCards,
            'statusLabel' => 'Jenis Transaksi',
            'statusOptions' => [
                'deposit' => 'Setoran',
                'withdrawal' => 'Penarikan',
            ],
        ];
    }

    private function loanReport(
        string $dateFrom,
        string $dateTo,
        string $search,
        ?string $status,
        bool $paginate
    ): array {
        $validStatuses = [
            'pending',
            'active',
            'rejected',
            'paid',
            'cancelled',
        ];

        $query = Loan::query()
            ->with([
                'member:id,member_number,name',
                'creator:id,name',
                'approver:id,name',
            ])
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('loan_number', 'like', "%{$search}%")
                        ->orWhereHas(
                            'member',
                            function (Builder $memberQuery) use ($search): void {
                                $memberQuery
                                    ->where('name', 'like', "%{$search}%")
                                    ->orWhere(
                                        'member_number',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        );
                });
            })
            ->when(
                in_array($status, $validStatuses, true),
                fn (Builder $query) => $query->where('status', $status)
            )
            ->whereDate('application_date', '>=', $dateFrom)
            ->whereDate('application_date', '<=', $dateTo);

        $summaryCards = [
            [
                'label' => 'Pokok Pinjaman',
                'value' => (float) (clone $query)
                    ->sum('principal_amount'),
                'format' => 'currency',
                'icon' => 'hand-coins',
                'class' => 'bg-blue-100 text-blue-600',
            ],
            [
                'label' => 'Total Tagihan',
                'value' => (float) (clone $query)
                    ->sum('total_amount'),
                'format' => 'currency',
                'icon' => 'landmark',
                'class' => 'bg-violet-100 text-violet-600',
            ],
            [
                'label' => 'Pinjaman Aktif',
                'value' => (clone $query)
                    ->where('status', 'active')
                    ->count(),
                'format' => 'number',
                'icon' => 'clock-3',
                'class' => 'bg-amber-100 text-amber-600',
            ],
            [
                'label' => 'Pinjaman Lunas',
                'value' => (clone $query)
                    ->where('status', 'paid')
                    ->count(),
                'format' => 'number',
                'icon' => 'badge-check',
                'class' => 'bg-emerald-100 text-emerald-600',
            ],
        ];

        $query
            ->latest('application_date')
            ->latest('id');

        return [
            'rows' => $this->finishQuery($query, $paginate),
            'summaryCards' => $summaryCards,
            'statusLabel' => 'Status Pinjaman',
            'statusOptions' => [
                'pending' => 'Menunggu Persetujuan',
                'active' => 'Aktif',
                'paid' => 'Lunas',
                'rejected' => 'Ditolak',
                'cancelled' => 'Dibatalkan',
            ],
        ];
    }

    private function installmentReport(
        string $dateFrom,
        string $dateTo,
        string $search,
        ?string $status,
        bool $paginate
    ): array {
        $query = InstallmentPayment::query()
            ->with([
                'installment:id,loan_id,installment_number,total_amount',
                'installment.loan:id,loan_number,member_id',
                'installment.loan.member:id,member_number,name',
                'user:id,name',
            ])
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('payment_code', 'like', "%{$search}%")
                        ->orWhere('reference_number', 'like', "%{$search}%")
                        ->orWhereHas(
                            'installment.loan',
                            function (Builder $loanQuery) use ($search): void {
                                $loanQuery
                                    ->where('loan_number', 'like', "%{$search}%")
                                    ->orWhereHas(
                                        'member',
                                        function (Builder $memberQuery) use ($search): void {
                                            $memberQuery
                                                ->where('name', 'like', "%{$search}%")
                                                ->orWhere(
                                                    'member_number',
                                                    'like',
                                                    "%{$search}%"
                                                );
                                        }
                                    );
                            }
                        );
                });
            })
            ->when(
                in_array($status, ['cash', 'transfer', 'other'], true),
                fn (Builder $query) => $query->where(
                    'payment_method',
                    $status
                )
            )
            ->whereDate('payment_date', '>=', $dateFrom)
            ->whereDate('payment_date', '<=', $dateTo);

        $summaryCards = [
            [
                'label' => 'Total Pembayaran',
                'value' => (float) (clone $query)->sum('amount'),
                'format' => 'currency',
                'icon' => 'circle-dollar-sign',
                'class' => 'bg-emerald-100 text-emerald-600',
            ],
            [
                'label' => 'Pembayaran Tunai',
                'value' => (float) (clone $query)
                    ->where('payment_method', 'cash')
                    ->sum('amount'),
                'format' => 'currency',
                'icon' => 'banknote',
                'class' => 'bg-blue-100 text-blue-600',
            ],
            [
                'label' => 'Pembayaran Transfer',
                'value' => (float) (clone $query)
                    ->where('payment_method', 'transfer')
                    ->sum('amount'),
                'format' => 'currency',
                'icon' => 'building-2',
                'class' => 'bg-violet-100 text-violet-600',
            ],
            [
                'label' => 'Jumlah Transaksi',
                'value' => (clone $query)->count(),
                'format' => 'number',
                'icon' => 'receipt-text',
                'class' => 'bg-amber-100 text-amber-600',
            ],
        ];

        $query
            ->latest('payment_date')
            ->latest('id');

        return [
            'rows' => $this->finishQuery($query, $paginate),
            'summaryCards' => $summaryCards,
            'statusLabel' => 'Metode Pembayaran',
            'statusOptions' => [
                'cash' => 'Tunai',
                'transfer' => 'Transfer',
                'other' => 'Lainnya',
            ],
        ];
    }

    private function cashReport(
        string $dateFrom,
        string $dateTo,
        string $search,
        ?string $status,
        bool $paginate
    ): array {
        $query = CashTransaction::query()
            ->with('user:id,name')
            ->when($search, function (Builder $query) use ($search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery
                        ->where('transaction_code', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when(
                in_array($status, ['income', 'expense'], true),
                fn (Builder $query) => $query->where(
                    'direction',
                    $status
                )
            )
            ->whereDate('transaction_date', '>=', $dateFrom)
            ->whereDate('transaction_date', '<=', $dateTo);

        $totalIncome = (float) (clone $query)
            ->where('direction', 'income')
            ->sum('amount');

        $totalExpense = (float) (clone $query)
            ->where('direction', 'expense')
            ->sum('amount');

        $summaryCards = [
            [
                'label' => 'Kas Masuk',
                'value' => $totalIncome,
                'format' => 'currency',
                'icon' => 'arrow-down-to-line',
                'class' => 'bg-emerald-100 text-emerald-600',
            ],
            [
                'label' => 'Kas Keluar',
                'value' => $totalExpense,
                'format' => 'currency',
                'icon' => 'arrow-up-from-line',
                'class' => 'bg-red-100 text-red-600',
            ],
            [
                'label' => 'Perubahan Saldo',
                'value' => $totalIncome - $totalExpense,
                'format' => 'currency',
                'icon' => 'wallet',
                'class' => 'bg-blue-100 text-blue-600',
            ],
            [
                'label' => 'Jumlah Transaksi',
                'value' => (clone $query)->count(),
                'format' => 'number',
                'icon' => 'receipt-text',
                'class' => 'bg-violet-100 text-violet-600',
            ],
        ];

        $query
            ->latest('transaction_date')
            ->latest('id');

        return [
            'rows' => $this->finishQuery($query, $paginate),
            'summaryCards' => $summaryCards,
            'statusLabel' => 'Jenis Transaksi',
            'statusOptions' => [
                'income' => 'Kas Masuk',
                'expense' => 'Kas Keluar',
            ],
        ];
    }

    private function finishQuery(
        Builder $query,
        bool $paginate
    ): mixed {
        if ($paginate) {
            return $query
                ->paginate(20)
                ->withQueryString();
        }

        return $query->get();
    }

    private function csvHeaders(string $reportType): array
    {
        return match ($reportType) {
            'members' => [
                'Nomor Anggota',
                'Nama',
                'Jenis Kelamin',
                'Telepon',
                'Email',
                'Tanggal Bergabung',
                'Status',
            ],

            'savings' => [
                'Kode Transaksi',
                'Tanggal',
                'Nomor Anggota',
                'Nama Anggota',
                'Jenis Simpanan',
                'Jenis Transaksi',
                'Nominal',
                'Saldo Akhir',
                'Petugas',
            ],

            'loans' => [
                'Nomor Pinjaman',
                'Tanggal Pengajuan',
                'Nomor Anggota',
                'Nama Anggota',
                'Pokok Pinjaman',
                'Bunga Per Bulan',
                'Tenor',
                'Total Tagihan',
                'Status',
            ],

            'installments' => [
                'Kode Pembayaran',
                'Tanggal',
                'Nomor Anggota',
                'Nama Anggota',
                'Nomor Pinjaman',
                'Angsuran Ke',
                'Metode',
                'Nominal',
                'Sisa Setelah Pembayaran',
                'Petugas',
            ],

            default => [
                'Kode Transaksi',
                'Tanggal',
                'Jenis',
                'Kategori',
                'Keterangan',
                'Metode',
                'Nominal',
                'Petugas',
            ],
        };
    }

    private function csvRow(
        string $reportType,
        mixed $row
    ): array {
        return match ($reportType) {
            'members' => [
                $row->member_number,
                $row->name,
                $row->gender_label,
                $row->phone,
                $row->email,
                $row->join_date->format('Y-m-d'),
                $row->status_label,
            ],

            'savings' => [
                $row->transaction_code,
                $row->transaction_date->format('Y-m-d'),
                $row->member->member_number,
                $row->member->name,
                $row->savingType->name,
                $row->transaction_type_label,
                (float) $row->amount,
                (float) $row->balance_after,
                $row->user?->name ?? 'Sistem',
            ],

            'loans' => [
                $row->loan_number,
                $row->application_date->format('Y-m-d'),
                $row->member->member_number,
                $row->member->name,
                (float) $row->principal_amount,
                (float) $row->interest_rate,
                $row->tenor_months,
                (float) $row->total_amount,
                $row->status_label,
            ],

            'installments' => [
                $row->payment_code,
                $row->payment_date->format('Y-m-d'),
                $row->installment->loan->member->member_number,
                $row->installment->loan->member->name,
                $row->installment->loan->loan_number,
                $row->installment->installment_number,
                $row->payment_method_label,
                (float) $row->amount,
                (float) $row->remaining_after,
                $row->user?->name ?? 'Sistem',
            ],

            default => [
                $row->transaction_code,
                $row->transaction_date->format('Y-m-d'),
                $row->direction_label,
                $row->category,
                $row->description,
                $row->payment_method_label,
                (float) $row->amount,
                $row->user?->name ?? 'Sistem',
            ],
        };
    }
}
