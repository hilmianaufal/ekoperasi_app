<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccount;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TrialBalanceService
{
    public function build(
        CarbonInterface $dateFrom,
        CarbonInterface $dateTo,
        ?string $accountType = null,
        bool $showZero = false
    ): array {
        $accounts = AccountingAccount::query()
            ->where('is_header', false)
            ->where('is_active', true)
            ->when(
                $accountType,
                fn ($query) => $query->where(
                    'type',
                    $accountType
                )
            )
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
                'type',
                'normal_balance',
            ]);

        $aggregates = collect();

        if ($accounts->isNotEmpty()) {
            $aggregates = DB::table(
                'journal_entry_lines as lines'
            )
                ->join(
                    'journal_entries as entries',
                    'entries.id',
                    '=',
                    'lines.journal_entry_id'
                )
                ->where(
                    'entries.status',
                    'posted'
                )
                ->whereDate(
                    'entries.entry_date',
                    '<=',
                    $dateTo->toDateString()
                )
                ->whereIn(
                    'lines.accounting_account_id',
                    $accounts->pluck('id')
                )
                ->select(
                    'lines.accounting_account_id'
                )
                ->selectRaw(
                    '
                    SUM(
                        CASE
                            WHEN entries.entry_date < ?
                            THEN lines.debit
                            ELSE 0
                        END
                    ) AS opening_debit
                    ',
                    [
                        $dateFrom->toDateString(),
                    ]
                )
                ->selectRaw(
                    '
                    SUM(
                        CASE
                            WHEN entries.entry_date < ?
                            THEN lines.credit
                            ELSE 0
                        END
                    ) AS opening_credit
                    ',
                    [
                        $dateFrom->toDateString(),
                    ]
                )
                ->selectRaw(
                    '
                    SUM(
                        CASE
                            WHEN entries.entry_date >= ?
                                AND entries.entry_date <= ?
                            THEN lines.debit
                            ELSE 0
                        END
                    ) AS period_debit
                    ',
                    [
                        $dateFrom->toDateString(),
                        $dateTo->toDateString(),
                    ]
                )
                ->selectRaw(
                    '
                    SUM(
                        CASE
                            WHEN entries.entry_date >= ?
                                AND entries.entry_date <= ?
                            THEN lines.credit
                            ELSE 0
                        END
                    ) AS period_credit
                    ',
                    [
                        $dateFrom->toDateString(),
                        $dateTo->toDateString(),
                    ]
                )
                ->groupBy(
                    'lines.accounting_account_id'
                )
                ->get()
                ->keyBy(
                    'accounting_account_id'
                );
        }

        $rows = $accounts
            ->map(function (
                AccountingAccount $account
            ) use ($aggregates): array {
                $aggregate = $aggregates->get(
                    $account->id
                );

                $openingDebitRaw = round(
                    (float) (
                        $aggregate->opening_debit
                        ?? 0
                    ),
                    2
                );

                $openingCreditRaw = round(
                    (float) (
                        $aggregate->opening_credit
                        ?? 0
                    ),
                    2
                );

                $periodDebit = round(
                    (float) (
                        $aggregate->period_debit
                        ?? 0
                    ),
                    2
                );

                $periodCredit = round(
                    (float) (
                        $aggregate->period_credit
                        ?? 0
                    ),
                    2
                );

                $openingNet = round(
                    $openingDebitRaw
                    - $openingCreditRaw,
                    2
                );

                $endingNet = round(
                    $openingNet
                    + $periodDebit
                    - $periodCredit,
                    2
                );

                return [
                    'id' => $account->id,
                    'code' => $account->code,
                    'name' => $account->name,
                    'type' => $account->type,
                    'type_label' =>
                        $account->type_label,

                    'normal_balance' =>
                        $account->normal_balance,

                    'normal_balance_label' =>
                        $account
                            ->normal_balance_label,

                    'opening_debit' => max(
                        $openingNet,
                        0
                    ),

                    'opening_credit' => max(
                        -$openingNet,
                        0
                    ),

                    'period_debit' =>
                        $periodDebit,

                    'period_credit' =>
                        $periodCredit,

                    'ending_debit' => max(
                        $endingNet,
                        0
                    ),

                    'ending_credit' => max(
                        -$endingNet,
                        0
                    ),

                    'has_activity' =>
                        abs($openingNet) >= 0.01
                        || abs($periodDebit) >= 0.01
                        || abs($periodCredit) >= 0.01,
                ];
            });

        if (!$showZero) {
            $rows = $rows
                ->filter(
                    fn (array $row): bool =>
                        $row['has_activity']
                )
                ->values();
        }

        $summary = $this->summary(
            $rows
        );

        return [
            'rows' => $rows,
            'summary' => $summary,
        ];
    }

    private function summary(
        Collection $rows
    ): array {
        $openingDebit = round(
            (float) $rows->sum(
                'opening_debit'
            ),
            2
        );

        $openingCredit = round(
            (float) $rows->sum(
                'opening_credit'
            ),
            2
        );

        $periodDebit = round(
            (float) $rows->sum(
                'period_debit'
            ),
            2
        );

        $periodCredit = round(
            (float) $rows->sum(
                'period_credit'
            ),
            2
        );

        $endingDebit = round(
            (float) $rows->sum(
                'ending_debit'
            ),
            2
        );

        $endingCredit = round(
            (float) $rows->sum(
                'ending_credit'
            ),
            2
        );

        $openingDifference = round(
            $openingDebit - $openingCredit,
            2
        );

        $periodDifference = round(
            $periodDebit - $periodCredit,
            2
        );

        $endingDifference = round(
            $endingDebit - $endingCredit,
            2
        );

        return [
            'account_count' =>
                $rows->count(),

            'opening_debit' =>
                $openingDebit,

            'opening_credit' =>
                $openingCredit,

            'opening_difference' =>
                $openingDifference,

            'period_debit' =>
                $periodDebit,

            'period_credit' =>
                $periodCredit,

            'period_difference' =>
                $periodDifference,

            'ending_debit' =>
                $endingDebit,

            'ending_credit' =>
                $endingCredit,

            'ending_difference' =>
                $endingDifference,

            'is_balanced' =>
                abs($openingDifference) < 0.01
                && abs($periodDifference) < 0.01
                && abs($endingDifference) < 0.01,
        ];
    }
}
