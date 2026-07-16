<?php

namespace App\Http\Controllers;

use App\Services\Accounting\TrialBalanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TrialBalanceController extends Controller
{
    public function index(
        Request $request,
        TrialBalanceService $service
    ): View {
        return view(
            'trial-balance.index',
            [
                ...$this->buildData(
                    $request,
                    $service
                ),
                'printMode' => false,
            ]
        );
    }

    public function print(
        Request $request,
        TrialBalanceService $service
    ): View {
        return view(
            'trial-balance.index',
            [
                ...$this->buildData(
                    $request,
                    $service
                ),
                'printMode' => true,
            ]
        );
    }

    public function export(
        Request $request,
        TrialBalanceService $service
    ): StreamedResponse {
        $data = $this->buildData(
            $request,
            $service
        );

        $rows = $data['rows'];
        $summary = $data['summary'];
        $dateFrom = $data['dateFrom'];
        $dateTo = $data['dateTo'];

        $fileName = sprintf(
            'neraca-saldo-%s-sampai-%s.csv',
            $dateFrom->format('Y-m-d'),
            $dateTo->format('Y-m-d')
        );

        return response()->streamDownload(
            function () use (
                $rows,
                $summary,
                $dateFrom,
                $dateTo
            ): void {
                $handle = fopen(
                    'php://output',
                    'w'
                );

                /*
                 * UTF-8 BOM supaya karakter
                 * terbaca baik di Microsoft Excel.
                 */
                fwrite(
                    $handle,
                    "\xEF\xBB\xBF"
                );

                fputcsv(
                    $handle,
                    [
                        'NERACA SALDO',
                    ],
                    ';'
                );

                fputcsv(
                    $handle,
                    [
                        'Periode',
                        $dateFrom->format('d-m-Y')
                        . ' s.d. '
                        . $dateTo->format('d-m-Y'),
                    ],
                    ';'
                );

                fputcsv(
                    $handle,
                    [],
                    ';'
                );

                fputcsv(
                    $handle,
                    [
                        'Kode Akun',
                        'Nama Akun',
                        'Kelompok',
                        'Saldo Normal',
                        'Saldo Awal Debit',
                        'Saldo Awal Kredit',
                        'Mutasi Debit',
                        'Mutasi Kredit',
                        'Saldo Akhir Debit',
                        'Saldo Akhir Kredit',
                    ],
                    ';'
                );

                foreach ($rows as $row) {
                    fputcsv(
                        $handle,
                        [
                            $row['code'],
                            $row['name'],
                            $row['type_label'],
                            $row[
                                'normal_balance_label'
                            ],
                            $row['opening_debit'],
                            $row['opening_credit'],
                            $row['period_debit'],
                            $row['period_credit'],
                            $row['ending_debit'],
                            $row['ending_credit'],
                        ],
                        ';'
                    );
                }

                fputcsv(
                    $handle,
                    [
                        '',
                        'TOTAL',
                        '',
                        '',
                        $summary[
                            'opening_debit'
                        ],
                        $summary[
                            'opening_credit'
                        ],
                        $summary[
                            'period_debit'
                        ],
                        $summary[
                            'period_credit'
                        ],
                        $summary[
                            'ending_debit'
                        ],
                        $summary[
                            'ending_credit'
                        ],
                    ],
                    ';'
                );

                fputcsv(
                    $handle,
                    [
                        '',
                        'SELISIH',
                        '',
                        '',
                        $summary[
                            'opening_difference'
                        ],
                        '',
                        $summary[
                            'period_difference'
                        ],
                        '',
                        $summary[
                            'ending_difference'
                        ],
                        '',
                    ],
                    ';'
                );

                fclose($handle);
            },
            $fileName,
            [
                'Content-Type' =>
                    'text/csv; charset=UTF-8',
            ]
        );
    }

    private function buildData(
        Request $request,
        TrialBalanceService $service
    ): array {
        $validated = $request->validate([
            'date_from' => [
                'nullable',
                'date',
            ],

            'date_to' => [
                'nullable',
                'date',
                'after_or_equal:date_from',
            ],

            'account_type' => [
                'nullable',
                Rule::in([
                    'asset',
                    'liability',
                    'equity',
                    'revenue',
                    'expense',
                ]),
            ],

            'show_zero' => [
                'nullable',
                'boolean',
            ],
        ], [
            'date_to.after_or_equal' =>
                'Tanggal akhir tidak boleh lebih kecil dari tanggal awal.',
        ]);

        $dateFrom = Carbon::parse(
            $validated['date_from']
            ?? now()
                ->startOfYear()
                ->toDateString()
        )->startOfDay();

        $dateTo = Carbon::parse(
            $validated['date_to']
            ?? now()->toDateString()
        )->endOfDay();

        $accountType =
            $validated['account_type']
            ?? null;

        $showZero = $request->boolean(
            'show_zero'
        );

        $report = $service->build(
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            accountType: $accountType,
            showZero: $showZero
        );

        return [
            ...$report,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'accountType' => $accountType,
            'showZero' => $showZero,
        ];
    }
}
