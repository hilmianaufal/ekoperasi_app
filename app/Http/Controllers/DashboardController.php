<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Member;
use App\Models\SavingTransaction;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $periodStart = now()->startOfMonth();
        $periodEnd = now()->endOfMonth();

        $memberSummary = [
            'total' => Member::query()->count(),
            'active' => Member::query()
                ->where('status', 'active')
                ->count(),
        ];

        $savingBalances = $this->savingBalances();

        $savingSummary = [
            'principal' => round(
                (float) ($savingBalances->get('POKOK') ?? 0),
                2
            ),
            'mandatory' => round(
                (float) ($savingBalances->get('WAJIB') ?? 0),
                2
            ),
            'voluntary' => round(
                (float) ($savingBalances->get('SUKARELA') ?? 0),
                2
            ),
            'total' => round(
                (float) $savingBalances->sum(),
                2
            ),
        ];

        $loanSummary = [
            'disbursed' => $this->totalDisbursedLoans(),
            'outstanding' => $this->totalOutstandingLoans(),
            'active_count' => Loan::query()
                ->where('status', 'active')
                ->count(),
            'pending_count' => Loan::query()
                ->where('status', 'pending')
                ->count(),
        ];

        $cashSummary = $this->cashSummary();

        $monthlySummary = $this->monthlySummary(
            $periodStart,
            $periodEnd
        );

        $recentTransactions = CashTransaction::query()
            ->with('user:id,name')
            ->latest('transaction_date')
            ->latest('id')
            ->limit(8)
            ->get();

        return view('dashboard', compact(
            'periodStart',
            'periodEnd',
            'memberSummary',
            'savingSummary',
            'loanSummary',
            'cashSummary',
            'monthlySummary',
            'recentTransactions'
        ));
    }

    private function savingBalances(): Collection
    {
        return SavingTransaction::query()
            ->join(
                'saving_types',
                'saving_types.id',
                '=',
                'saving_transactions.saving_type_id'
            )
            ->selectRaw('UPPER(saving_types.code) AS saving_code')
            ->selectRaw("\n                COALESCE(\n                    SUM(\n                        CASE\n                            WHEN saving_transactions.transaction_type = 'deposit'\n                                THEN saving_transactions.amount\n                            WHEN saving_transactions.transaction_type = 'withdrawal'\n                                THEN -saving_transactions.amount\n                            ELSE 0\n                        END\n                    ),\n                    0\n                ) AS balance\n            ")
            ->groupByRaw('UPPER(saving_types.code)')
            ->pluck('balance', 'saving_code');
    }

    private function totalDisbursedLoans(): float
    {
        $total = Loan::query()
            ->whereIn('status', [
                'active',
                'paid',
            ])
            ->selectRaw("\n                COALESCE(\n                    SUM(\n                        CASE\n                            WHEN COALESCE(is_legacy, 0) = 1 THEN\n                                CASE\n                                    WHEN (\n                                        COALESCE(opening_principal, 0)\n                                        + COALESCE(disbursed_during_import, 0)\n                                    ) > 0\n                                        THEN COALESCE(opening_principal, 0)\n                                            + COALESCE(disbursed_during_import, 0)\n                                    ELSE COALESCE(principal_amount, 0)\n                                END\n                            ELSE COALESCE(principal_amount, 0)\n                        END\n                    ),\n                    0\n                ) AS total\n            ")
            ->value('total');

        return round((float) $total, 2);
    }

    private function totalOutstandingLoans(): float
    {
        $regularLoanTotal = Loan::query()
            ->where('status', 'active')
            ->where(function ($query): void {
                $query
                    ->where('is_legacy', false)
                    ->orWhereNull('is_legacy');
            })
            ->sum('total_amount');

        $regularPaidTotal = LoanInstallment::query()
            ->whereHas('loan', function ($query): void {
                $query
                    ->where('status', 'active')
                    ->where(function ($loanQuery): void {
                        $loanQuery
                            ->where('is_legacy', false)
                            ->orWhereNull('is_legacy');
                    });
            })
            ->sum('paid_amount');

        $legacyOutstanding = Loan::query()
            ->where('status', 'active')
            ->where('is_legacy', true)
            ->sum('outstanding_principal');

        return round(
            max(
                (float) $regularLoanTotal
                    - (float) $regularPaidTotal,
                0
            ) + max((float) $legacyOutstanding, 0),
            2
        );
    }

    private function cashSummary(): array
    {
        $balances = CashTransaction::query()
            ->select('payment_method')
            ->selectRaw("\n                COALESCE(\n                    SUM(\n                        CASE\n                            WHEN direction = 'income' THEN amount\n                            ELSE -amount\n                        END\n                    ),\n                    0\n                ) AS balance\n            ")
            ->groupBy('payment_method')
            ->pluck('balance', 'payment_method');

        $cash = round(
            (float) ($balances->get('cash') ?? 0),
            2
        );

        $bank = round(
            (float) ($balances->get('transfer') ?? 0),
            2
        );

        $other = round(
            (float) ($balances->get('other') ?? 0),
            2
        );

        return [
            'cash' => $cash,
            'bank' => $bank,
            'other' => $other,
            'total' => round($cash + $bank + $other, 2),
        ];
    }

    private function monthlySummary(
        Carbon $periodStart,
        Carbon $periodEnd
    ): array {
        $paymentQuery = InstallmentPayment::query()
            ->whereBetween('payment_date', [
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            ]);

        $installments = round(
            (float) (clone $paymentQuery)->sum('amount'),
            2
        );

        $principalInstallments = round(
            (float) (clone $paymentQuery)->sum('principal_amount'),
            2
        );

        $profitShare = round(
            (float) (clone $paymentQuery)->sum('profit_share_amount'),
            2
        );

        $legacyAdministration = round(
            (float) (clone $paymentQuery)->sum('administration_fee'),
            2
        );

        $loanAdministration = round(
            (float) Loan::query()
                ->whereBetween('administration_collected_at', [
                    $periodStart,
                    $periodEnd,
                ])
                ->sum('administration_fee'),
            2
        );

        $savingDeposits = round(
            (float) SavingTransaction::query()
                ->where('transaction_type', 'deposit')
                ->whereBetween('transaction_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ])
                ->sum('amount'),
            2
        );

        $savingWithdrawals = round(
            (float) SavingTransaction::query()
                ->where('transaction_type', 'withdrawal')
                ->whereBetween('transaction_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ])
                ->sum('amount'),
            2
        );

        $loanDisbursements = round(
            (float) Loan::query()
                ->whereIn('status', [
                    'active',
                    'paid',
                ])
                ->whereBetween('start_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ])
                ->get([
                    'is_legacy',
                    'principal_amount',
                    'disbursed_during_import',
                ])
                ->sum(function (Loan $loan): float {
                    if ((bool) $loan->is_legacy) {
                        return (float) $loan->disbursed_during_import;
                    }

                    return (float) $loan->principal_amount;
                }),
            2
        );

        $cashOut = round(
            (float) CashTransaction::query()
                ->where('direction', 'expense')
                ->whereBetween('transaction_date', [
                    $periodStart->toDateString(),
                    $periodEnd->toDateString(),
                ])
                ->sum('amount'),
            2
        );

        return [
            'installments' => $installments,
            'principal_installments' => $principalInstallments,
            'profit_share' => $profitShare,
            'administration' => round(
                $loanAdministration + $legacyAdministration,
                2
            ),
            'saving_deposits' => $savingDeposits,
            'saving_withdrawals' => $savingWithdrawals,
            'loan_disbursements' => $loanDisbursements,
            'cash_out' => $cashOut,
        ];
    }
}
