<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\ImportRow;
use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Member;
use App\Models\SavingTransaction;
use App\Models\SavingType;
use DomainException;

class ImportReconciliationService
{
    /**
     * Membuat laporan rekonsiliasi antara data sumber Excel
     * dengan data yang sudah tersimpan di aplikasi.
     *
     * @return array<string, mixed>
     */
    public function build(
        ImportBatch $batch
    ): array {
        $batch->refresh();

        if (!$batch->members_savings_imported_at) {
            throw new DomainException(
                'Data anggota dan simpanan belum diimpor.'
            );
        }

        if (!$batch->financing_imported_at) {
            throw new DomainException(
                'Data pembiayaan dan angsuran belum diimpor.'
            );
        }

        $sourceNumbers = $batch
            ->mappings()
            ->where('status', 'imported')
            ->whereNotNull('member_id')
            ->pluck('source_number');

        if ($sourceNumbers->isEmpty()) {
            throw new DomainException(
                'Tidak ditemukan anggota hasil import untuk direkonsiliasi.'
            );
        }

        $rows = ImportRow::query()
            ->where('import_batch_id', $batch->id)
            ->whereIn('source_number', $sourceNumbers)
            ->when(
                $batch->cutoff_date,
                fn ($query) => $query->whereDate(
                    'period_date',
                    '<=',
                    $batch->cutoff_date
                )
            )
            ->orderBy('period_date')
            ->orderBy('row_number')
            ->get();

        if ($rows->isEmpty()) {
            throw new DomainException(
                'Baris sumber Excel tidak ditemukan.'
            );
        }

        /*
         * Karena data sudah diurutkan berdasarkan periode,
         * last() akan mengambil saldo terakhir setiap anggota.
         */
        $latestRows = $rows
            ->groupBy('source_number')
            ->map(
                fn ($memberRows) => $memberRows->last()
            );

        $sourceTotals = [
            'principal_deposit' => $this->money(
                (float) $rows->sum('principal_saving')
            ),

            'mandatory_deposit' => $this->money(
                (float) $rows->sum('mandatory_saving')
            ),

            'mandatory_balance' => $this->money(
                (float) $latestRows->sum('mandatory_balance')
            ),

            'voluntary_deposit' => $this->money(
                (float) $rows->sum('voluntary_saving')
            ),

            'voluntary_withdrawal' => $this->money(
                (float) $rows->sum('voluntary_withdrawal')
            ),

            'voluntary_balance' => $this->money(
                (float) $latestRows->sum('voluntary_balance')
            ),

            'new_financing' => $this->money(
                (float) $rows->sum('new_financing')
            ),

            'principal_payment' => $this->money(
                (float) $rows->sum('principal_installment')
            ),

            'profit_share' => $this->money(
                (float) $rows->sum('profit_share')
            ),

            'administration_fee' => $this->money(
                (float) $rows->sum('administration_fee')
            ),

            'remaining_financing' => $this->money(
                (float) $latestRows->sum('remaining_financing')
            ),
        ];

        /*
         * Total transaksi asli tanpa saldo awal dan penyesuaian.
         */
        $savingComponentTotals = SavingTransaction::query()
            ->where('import_batch_id', $batch->id)
            ->whereIn('import_component', [
                'principal_deposit',
                'mandatory_deposit',
                'voluntary_deposit',
                'voluntary_withdrawal',
            ])
            ->selectRaw(
                'import_component, COALESCE(SUM(amount), 0) AS total'
            )
            ->groupBy('import_component')
            ->pluck('total', 'import_component');

        $savingTypeIds = SavingType::query()
            ->whereIn('code', [
                'POKOK',
                'WAJIB',
                'SUKARELA',
            ])
            ->pluck('id');

        /*
         * Ambil transaksi terakhir setiap anggota dan jenis simpanan
         * untuk memperoleh saldo akhir pada tanggal cut-off.
         */
        $latestTransactionIds = SavingTransaction::query()
            ->where('import_batch_id', $batch->id)
            ->whereIn('saving_type_id', $savingTypeIds)
            ->when(
                $batch->cutoff_date,
                fn ($query) => $query->whereDate(
                    'transaction_date',
                    '<=',
                    $batch->cutoff_date
                )
            )
            ->selectRaw('MAX(id) AS id')
            ->groupBy([
                'member_id',
                'saving_type_id',
            ])
            ->pluck('id');

        $actualSavingBalances = SavingTransaction::query()
            ->join(
                'saving_types',
                'saving_types.id',
                '=',
                'saving_transactions.saving_type_id'
            )
            ->whereIn(
                'saving_transactions.id',
                $latestTransactionIds
            )
            ->selectRaw(
                'saving_types.code AS saving_code'
            )
            ->selectRaw(
                'COALESCE(SUM(saving_transactions.balance_after), 0) AS total'
            )
            ->groupBy('saving_types.code')
            ->pluck('total', 'saving_code');

        $loanTotals = Loan::query()
            ->where('import_batch_id', $batch->id)
            ->selectRaw('COUNT(*) AS loan_count')
            ->selectRaw(
                'COALESCE(SUM(disbursed_during_import), 0) AS new_financing'
            )
            ->selectRaw(
                'COALESCE(SUM(outstanding_principal), 0) AS outstanding_principal'
            )
            ->selectRaw(
                'COALESCE(SUM(profit_share_paid), 0) AS profit_share'
            )
            ->selectRaw(
                'COALESCE(SUM(administration_paid), 0) AS administration_fee'
            )
            ->first();

        $paymentTotals = InstallmentPayment::query()
            ->where('import_batch_id', $batch->id)
            ->selectRaw('COUNT(*) AS payment_count')
            ->selectRaw(
                'COALESCE(SUM(principal_amount), 0) AS principal_payment'
            )
            ->selectRaw(
                'COALESCE(SUM(profit_share_amount), 0) AS profit_share'
            )
            ->selectRaw(
                'COALESCE(SUM(administration_fee), 0) AS administration_fee'
            )
            ->first();

        $sourceLoanCount = $rows
            ->groupBy('source_number')
            ->filter(function ($memberRows): bool {
                return $memberRows->contains(
                    function (ImportRow $row): bool {
                        return (
                            (float) $row->new_financing > 0
                            || (float) $row->principal_installment > 0
                            || (float) $row->profit_share > 0
                            || (float) $row->administration_fee > 0
                            || (float) $row->remaining_financing > 0
                        );
                    }
                );
            })
            ->count();

        /*
         * Service import membuat jadwal angsuran apabila
         * terdapat angsuran pokok atau pembayaran bagi hasil.
         */
        $sourceInstallmentCount = $rows
            ->filter(function (ImportRow $row): bool {
                return (
                    (float) $row->principal_installment > 0
                    || (float) $row->profit_share > 0
                );
            })
            ->count();

        $memberIds = $batch
            ->mappings()
            ->where('status', 'imported')
            ->whereNotNull('member_id')
            ->pluck('member_id')
            ->unique();

        $actualMemberCount = Member::query()
            ->whereIn('id', $memberIds)
            ->count();

        $actualInstallmentCount = LoanInstallment::query()
            ->where('import_batch_id', $batch->id)
            ->count();

        $sections = [
            [
                'title' => 'Data Anggota',
                'description'
                    => 'Perbandingan anggota yang diproses dari mapping dengan anggota yang tersedia di aplikasi.',

                'metrics' => [
                    $this->numberMetric(
                        label: 'Jumlah Anggota',
                        source: $sourceNumbers->count(),
                        actual: $actualMemberCount
                    ),
                ],
            ],

            [
                'title' => 'Simpanan Anggota',
                'description'
                    => 'Perbandingan setoran, penarikan, dan saldo akhir simpanan pada tanggal cut-off.',

                'metrics' => [
                    $this->moneyMetric(
                        label: 'Setoran Simpanan Pokok',
                        source: $sourceTotals[
                            'principal_deposit'
                        ],
                        actual: (float) (
                            $savingComponentTotals[
                                'principal_deposit'
                            ] ?? 0
                        )
                    ),

                    $this->moneyMetric(
                        label: 'Setoran Simpanan Wajib',
                        source: $sourceTotals[
                            'mandatory_deposit'
                        ],
                        actual: (float) (
                            $savingComponentTotals[
                                'mandatory_deposit'
                            ] ?? 0
                        )
                    ),

                    $this->moneyMetric(
                        label: 'Saldo Simpanan Wajib',
                        source: $sourceTotals[
                            'mandatory_balance'
                        ],
                        actual: (float) (
                            $actualSavingBalances[
                                'WAJIB'
                            ] ?? 0
                        )
                    ),

                    $this->moneyMetric(
                        label: 'Setoran Simpanan Sukarela',
                        source: $sourceTotals[
                            'voluntary_deposit'
                        ],
                        actual: (float) (
                            $savingComponentTotals[
                                'voluntary_deposit'
                            ] ?? 0
                        )
                    ),

                    $this->moneyMetric(
                        label: 'Penarikan Simpanan Sukarela',
                        source: $sourceTotals[
                            'voluntary_withdrawal'
                        ],
                        actual: (float) (
                            $savingComponentTotals[
                                'voluntary_withdrawal'
                            ] ?? 0
                        )
                    ),

                    $this->moneyMetric(
                        label: 'Saldo Simpanan Sukarela',
                        source: $sourceTotals[
                            'voluntary_balance'
                        ],
                        actual: (float) (
                            $actualSavingBalances[
                                'SUKARELA'
                            ] ?? 0
                        )
                    ),
                ],
            ],

            [
                'title' => 'Pembiayaan dan Angsuran',
                'description'
                    => 'Perbandingan pembiayaan, pembayaran, bagi hasil, administrasi, dan saldo akhir.',

                'metrics' => [
                    $this->numberMetric(
                        label: 'Jumlah Pembiayaan',
                        source: $sourceLoanCount,
                        actual: (int) (
                            $loanTotals?->loan_count ?? 0
                        )
                    ),

                    $this->moneyMetric(
                        label: 'Pembiayaan Baru',
                        source: $sourceTotals[
                            'new_financing'
                        ],
                        actual: (float) (
                            $loanTotals?->new_financing ?? 0
                        )
                    ),

                    $this->moneyMetric(
                        label: 'Angsuran Pokok',
                        source: $sourceTotals[
                            'principal_payment'
                        ],
                        actual: (float) (
                            $paymentTotals?->principal_payment ?? 0
                        )
                    ),

                    $this->moneyMetric(
                        label: 'Bagi Hasil',
                        source: $sourceTotals[
                            'profit_share'
                        ],
                        actual: (float) (
                            $paymentTotals?->profit_share ?? 0
                        )
                    ),

                    /*
                     * Biaya administrasi disimpan pada pembiayaan,
                     * termasuk ketika tidak ada pembayaran pokok.
                     */
                    $this->moneyMetric(
                        label: 'Biaya Administrasi',
                        source: $sourceTotals[
                            'administration_fee'
                        ],
                        actual: (float) (
                            $loanTotals?->administration_fee ?? 0
                        )
                    ),

                    $this->moneyMetric(
                        label: 'Sisa Pembiayaan Cut-off',
                        source: $sourceTotals[
                            'remaining_financing'
                        ],
                        actual: (float) (
                            $loanTotals?->outstanding_principal ?? 0
                        )
                    ),

                    $this->numberMetric(
                        label: 'Jumlah Angsuran',
                        source: $sourceInstallmentCount,
                        actual: $actualInstallmentCount
                    ),

                    $this->numberMetric(
                        label: 'Jumlah Pembayaran',
                        source: $sourceInstallmentCount,
                        actual: (int) (
                            $paymentTotals?->payment_count ?? 0
                        )
                    ),
                ],
            ],
        ];

        $allMetrics = collect($sections)
            ->flatMap(
                fn (array $section) => $section['metrics']
            );

        $matchedCount = $allMetrics
            ->where('matched', true)
            ->count();

        $differenceCount = $allMetrics
            ->where('matched', false)
            ->count();

        return [
            'batch' => $batch,
            'sections' => $sections,

            'summary' => [
                'metric_count' => $allMetrics->count(),
                'matched_count' => $matchedCount,
                'difference_count' => $differenceCount,
                'all_matched' => $differenceCount === 0,
            ],

            'generated_at' => now(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function moneyMetric(
        string $label,
        float $source,
        float $actual
    ): array {
        $source = $this->money($source);
        $actual = $this->money($actual);
        $difference = $this->money(
            $actual - $source
        );

        return [
            'label' => $label,
            'format' => 'money',
            'source' => $source,
            'actual' => $actual,
            'difference' => $difference,
            'matched' => abs($difference) < 0.01,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function numberMetric(
        string $label,
        int $source,
        int $actual
    ): array {
        $difference = $actual - $source;

        return [
            'label' => $label,
            'format' => 'number',
            'source' => $source,
            'actual' => $actual,
            'difference' => $difference,
            'matched' => $difference === 0,
        ];
    }

    private function money(
        float $value
    ): float {
        return round($value, 2);
    }
}
