<?php

namespace App\Http\Controllers;

use App\Models\FinancialStatementPeriod;
use App\Services\FinancialStatements\FinancialPositionService;
use Illuminate\View\View;

class FinancialStatementController extends Controller
{
    public function index(): View
    {
        $periods = FinancialStatementPeriod::query()
            ->with([
                'creator:id,name',
                'approver:id,name',
            ])
            ->latest('report_date')
            ->paginate(10);

        return view(
            'financial-statements.index',
            compact('periods')
        );
    }

    public function show(
        FinancialStatementPeriod $financialStatementPeriod,
        FinancialPositionService $service
    ): View {
        $financialStatementPeriod->load([
            'creator:id,name',
            'approver:id,name',
        ]);

        $report = $service->build(
            $financialStatementPeriod
        );

        return view(
            'financial-statements.show',
            $report
        );
    }
}
