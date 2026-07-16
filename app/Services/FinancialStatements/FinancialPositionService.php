<?php

namespace App\Services\FinancialStatements;

use App\Models\CashTransaction;
use App\Models\FinancialStatementPeriod;
use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\LoanImportEntry;
use App\Models\SavingTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialPositionService
{
    private const MANUAL_DEFAULTS = [
        'bank' => 0,
        'secondary_savings' => 0,
        'fixed_assets' => 0,
        'accumulated_depreciation' => 0,
        'other_assets' => 0,
        'other_liabilities' => 0,
        'grant' => 0,
        'reserve' => 0,
        'current_shu' => 0,
        'other_equity' => 0,
    ];

    private const REFERENCE_DEFAULTS = [
        'cash' => 0,
        'bank' => 0,
        'financing' => 0,
        'secondary_savings' => 0,
        'fixed_assets' => 0,
        'accumulated_depreciation' => 0,
        'other_assets' => 0,
        'total_assets' => 0,

        'other_liabilities' => 0,
        'total_liabilities' => 0,

        'principal_savings' => 0,
        'mandatory_savings' => 0,
        'grant' => 0,
        'reserve' => 0,
        'current_shu' => 0,
        'other_equity' => 0,
        'total_equity' => 0,

        'total_liabilities_equity' => 0,
    ];

    public function build(
        FinancialStatementPeriod $period
    ): array {
        $reportDate = $period->report_date
            ->copy();

        $manual = array_merge(
            self::MANUAL_DEFAULTS,
            $period->manual_balances ?? []
        );

        $reference = array_merge(
            self::REFERENCE_DEFAULTS,
            $period->reference_balances ?? []
        );

        $cashMovement = $this->cashMovement(
            $reportDate
        );

        $cashBalance = round(
            (float) $period->opening_cash_balance
            + $cashMovement,
            2
        );

        $financingBalance =
            $this->financingBalance(
                $reportDate
            );

        $principalSavings =
            $this->savingBalance(
                'POKOK',
                $reportDate
            );

        $mandatorySavings =
            $this->savingBalance(
                'WAJIB',
                $reportDate
            );

        $assets = [
            'cash' => $cashBalance,
            'bank' => $this->money(
                $manual['bank']
            ),
            'financing' => $financingBalance,
            'secondary_savings' => $this->money(
                $manual['secondary_savings']
            ),
            'fixed_assets' => $this->money(
                $manual['fixed_assets']
            ),
            'accumulated_depreciation' =>
                $this->money(
                    $manual[
                        'accumulated_depreciation'
                    ]
                ),
            'other_assets' => $this->money(
                $manual['other_assets']
            ),
        ];

        $totalAssets = round(
            $assets['cash']
            + $assets['bank']
            + $assets['financing']
            + $assets['secondary_savings']
            + $assets['fixed_assets']
            - $assets['accumulated_depreciation']
            + $assets['other_assets'],
            2
        );

        $liabilities = [
            'other_liabilities' => $this->money(
                $manual['other_liabilities']
            ),
        ];

        $totalLiabilities = round(
            array_sum($liabilities),
            2
        );

        $equity = [
            'principal_savings' =>
                $principalSavings,

            'mandatory_savings' =>
                $mandatorySavings,

            'grant' => $this->money(
                $manual['grant']
            ),

            'reserve' => $this->money(
                $manual['reserve']
            ),

            'current_shu' => $this->money(
                $manual['current_shu']
            ),

            'other_equity' => $this->money(
                $manual['other_equity']
            ),
        ];

        $totalEquity = round(
            array_sum($equity),
            2
        );

        $totalLiabilitiesEquity = round(
            $totalLiabilities + $totalEquity,
            2
        );

        $application = [
            ...$assets,
            'total_assets' => $totalAssets,

            ...$liabilities,
            'total_liabilities' =>
                $totalLiabilities,

            ...$equity,
            'total_equity' => $totalEquity,

            'total_liabilities_equity' =>
                $totalLiabilitiesEquity,
        ];

        $accounts = $this->accountRows(
            $application,
            $reference
        );

        $differenceCount = collect($accounts)
            ->where('matched', false)
            ->count();

        $balanceDifference = round(
            $totalAssets
            - $totalLiabilitiesEquity,
            2
        );

        return [
            'period' => $period,
            'report_date' => $reportDate,
            'manual' => $manual,
            'reference' => $reference,
            'application' => $application,
            'accounts' => $accounts,

            'summary' => [
                'total_assets' =>
                    $totalAssets,

                'total_liabilities' =>
                    $totalLiabilities,

                'total_equity' =>
                    $totalEquity,

                'total_liabilities_equity' =>
                    $totalLiabilitiesEquity,

                'balance_difference' =>
                    $balanceDifference,

                'reference_total_assets' =>
                    $this->money(
                        $reference['total_assets']
                    ),

                'reference_total_liabilities_equity' =>
                    $this->money(
                        $reference[
                            'total_liabilities_equity'
                        ]
                    ),

                'account_count' =>
                    count($accounts),

                'matched_count' =>
                    count($accounts)
                    - $differenceCount,

                'difference_count' =>
                    $differenceCount,

                'cash_movement' =>
                    $cashMovement,

                'opening_cash' =>
                    (float) $period
                        ->opening_cash_balance,
            ],
        ];
    }

    private function cashMovement(
        Carbon $reportDate
    ): float {
        $startDate = $reportDate
            ->copy()
            ->startOfYear();

        return round(
            (float) CashTransaction::query()
                ->whereDate(
                    'transaction_date',
                    '>=',
                    $startDate
                )
                ->whereDate(
                    'transaction_date',
                    '<=',
                    $reportDate
                )
                ->selectRaw("
                    COALESCE(
                        SUM(
                            CASE
                                WHEN direction = 'income'
                                THEN amount
                                ELSE -amount
                            END
                        ),
                        0
                    ) AS net_cash
                ")
                ->value('net_cash'),
            2
        );
    }

    private function financingBalance(
        Carbon $reportDate
    ): float {
        /*
         * Pembiayaan hasil migrasi:
         * gunakan sisa pembiayaan terakhir
         * yang tercatat sebelum atau pada tanggal laporan.
         */
        $legacyEntries = LoanImportEntry::query()
            ->whereDate(
                'period_date',
                '<=',
                $reportDate
            )
            ->orderBy('period_date')
            ->orderBy('id')
            ->get([
                'id',
                'loan_id',
                'period_date',
                'reported_remaining',
            ]);

        $legacyBalance = $legacyEntries
            ->groupBy('loan_id')
            ->sum(function (
                Collection $entries
            ): float {
                return (float) $entries
                    ->last()
                    ->reported_remaining;
            });

        /*
         * Pembiayaan baru yang dibuat langsung
         * melalui aplikasi.
         */
        $normalLoans = Loan::query()
            ->where(function ($query): void {
                $query
                    ->whereNull('is_legacy')
                    ->orWhere(
                        'is_legacy',
                        false
                    );
            })
            ->whereNotIn('status', [
                'rejected',
                'cancelled',
            ])
            ->whereDate(
                'application_date',
                '<=',
                $reportDate
            )
            ->get([
                'id',
                'principal_amount',
            ]);

        $normalBalance = $normalLoans
            ->sum(function (
                Loan $loan
            ) use ($reportDate): float {
                $principalPaid =
                    (float) InstallmentPayment::query()
                        ->whereHas(
                            'installment',
                            fn ($query) =>
                                $query->where(
                                    'loan_id',
                                    $loan->id
                                )
                        )
                        ->whereDate(
                            'payment_date',
                            '<=',
                            $reportDate
                        )
                        ->sum(
                            'principal_amount'
                        );

                return max(
                    (float) $loan->principal_amount
                    - $principalPaid,
                    0
                );
            });

        return round(
            $legacyBalance + $normalBalance,
            2
        );
    }

    private function savingBalance(
        string $code,
        Carbon $reportDate
    ): float {
        return round(
            (float) SavingTransaction::query()
                ->join(
                    'saving_types',
                    'saving_types.id',
                    '=',
                    'saving_transactions.saving_type_id'
                )
                ->where(
                    'saving_types.code',
                    $code
                )
                ->whereDate(
                    'saving_transactions.transaction_date',
                    '<=',
                    $reportDate
                )
                ->selectRaw("
                    COALESCE(
                        SUM(
                            CASE
                                WHEN saving_transactions.transaction_type = 'deposit'
                                THEN saving_transactions.amount
                                ELSE -saving_transactions.amount
                            END
                        ),
                        0
                    ) AS balance
                ")
                ->value('balance'),
            2
        );
    }

    private function accountRows(
        array $application,
        array $reference
    ): array {
        $definitions = [
            [
                'section' => 'assets',
                'key' => 'cash',
                'label' => 'Kas dan setara kas',
                'source' => 'Otomatis',
            ],
            [
                'section' => 'assets',
                'key' => 'bank',
                'label' => 'Bank',
                'source' => 'Manual',
            ],
            [
                'section' => 'assets',
                'key' => 'financing',
                'label' => 'Pembiayaan mudharabah',
                'source' => 'Otomatis',
            ],
            [
                'section' => 'assets',
                'key' => 'secondary_savings',
                'label' => 'Simpanan pada sekunder',
                'source' => 'Manual',
            ],
            [
                'section' => 'assets',
                'key' => 'fixed_assets',
                'label' => 'Aset tetap',
                'source' => 'Manual',
            ],
            [
                'section' => 'assets',
                'key' => 'accumulated_depreciation',
                'label' => 'Akumulasi penyusutan',
                'source' => 'Manual',
            ],
            [
                'section' => 'assets',
                'key' => 'other_assets',
                'label' => 'Aset lain',
                'source' => 'Manual',
            ],
            [
                'section' => 'assets',
                'key' => 'total_assets',
                'label' => 'Total Aset',
                'source' => 'Hasil Perhitungan',
                'is_total' => true,
            ],

            [
                'section' => 'liabilities',
                'key' => 'other_liabilities',
                'label' => 'Liabilitas lain',
                'source' => 'Manual',
            ],
            [
                'section' => 'liabilities',
                'key' => 'total_liabilities',
                'label' => 'Total Liabilitas',
                'source' => 'Hasil Perhitungan',
                'is_total' => true,
            ],

            [
                'section' => 'equity',
                'key' => 'principal_savings',
                'label' => 'Simpanan Pokok',
                'source' => 'Otomatis',
            ],
            [
                'section' => 'equity',
                'key' => 'mandatory_savings',
                'label' => 'Simpanan Wajib',
                'source' => 'Otomatis',
            ],
            [
                'section' => 'equity',
                'key' => 'grant',
                'label' => 'Hibah',
                'source' => 'Manual',
            ],
            [
                'section' => 'equity',
                'key' => 'reserve',
                'label' => 'Cadangan Koperasi',
                'source' => 'Manual',
            ],
            [
                'section' => 'equity',
                'key' => 'current_shu',
                'label' => 'Sisa Hasil Usaha',
                'source' => 'Manual',
            ],
            [
                'section' => 'equity',
                'key' => 'other_equity',
                'label' => 'Ekuitas lain',
                'source' => 'Manual',
            ],
            [
                'section' => 'equity',
                'key' => 'total_equity',
                'label' => 'Total Ekuitas',
                'source' => 'Hasil Perhitungan',
                'is_total' => true,
            ],
            [
                'section' => 'summary',
                'key' => 'total_liabilities_equity',
                'label' => 'Total Liabilitas dan Ekuitas',
                'source' => 'Hasil Perhitungan',
                'is_total' => true,
            ],
        ];

        return collect($definitions)
            ->map(function (
                array $definition
            ) use (
                $application,
                $reference
            ): array {
                $applicationValue =
                    $this->money(
                        $application[
                            $definition['key']
                        ] ?? 0
                    );

                $referenceValue =
                    $this->money(
                        $reference[
                            $definition['key']
                        ] ?? 0
                    );

                $difference = round(
                    $applicationValue
                    - $referenceValue,
                    2
                );

                return [
                    ...$definition,

                    'application' =>
                        $applicationValue,

                    'reference' =>
                        $referenceValue,

                    'difference' =>
                        $difference,

                    'matched' =>
                        abs($difference) < 0.01,

                    'is_total' =>
                        $definition['is_total']
                        ?? false,
                ];
            })
            ->all();
    }

    private function money(
        mixed $value
    ): float {
        return round(
            (float) $value,
            2
        );
    }
}
