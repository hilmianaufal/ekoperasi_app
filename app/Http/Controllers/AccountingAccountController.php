<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountingAccountController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->input('search')
        );

        $type = $request->input('type');

        $accounts = AccountingAccount::query()
            ->with([
                'parent:id,code,name',
                'mapping:id,mapping_key,accounting_account_id',
            ])
            ->when(
                $search,
                function ($query) use ($search): void {
                    $query->where(
                        function ($subQuery) use ($search): void {
                            $subQuery
                                ->where(
                                    'code',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'name',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                in_array(
                    $type,
                    [
                        'asset',
                        'liability',
                        'equity',
                        'revenue',
                        'expense',
                    ],
                    true
                ),
                fn ($query) => $query->where(
                    'type',
                    $type
                )
            )
            ->orderBy('code')
            ->paginate(30)
            ->withQueryString();

        $statistics = [
            'total' => AccountingAccount::query()
                ->count(),

            'active' => AccountingAccount::query()
                ->where('is_active', true)
                ->count(),

            'headers' => AccountingAccount::query()
                ->where('is_header', true)
                ->count(),

            'transaction_accounts' =>
                AccountingAccount::query()
                    ->where('is_header', false)
                    ->count(),
        ];

        return view(
            'accounting-accounts.index',
            compact(
                'accounts',
                'statistics',
                'search',
                'type'
            )
        );
    }
}
