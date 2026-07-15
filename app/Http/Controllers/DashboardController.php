<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\Loan;
use App\Models\Member;
use App\Models\SavingTransaction;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalDeposits = (float) SavingTransaction::query()
            ->where('transaction_type', 'deposit')
            ->sum('amount');

        $totalWithdrawals = (float) SavingTransaction::query()
            ->where('transaction_type', 'withdrawal')
            ->sum('amount');

        $totalIncome = (float) CashTransaction::income()
            ->sum('amount');

        $totalExpense = (float) CashTransaction::expense()
            ->sum('amount');

        $statistics = [
            'members' => Member::query()
                ->where('status', 'active')
                ->count(),

            'savings' => $totalDeposits - $totalWithdrawals,

            'activeLoans' => Loan::query()
                ->where('status', 'active')
                ->count(),

            'cashBalance' => $totalIncome - $totalExpense,
        ];

        return view('dashboard', compact('statistics'));
    }
}
