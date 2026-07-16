<?php

namespace App\Http\Controllers;

use App\Models\CashTransaction;
use App\Models\ShuMemberAllocation;
use App\Models\ShuPayment;
use App\Models\ShuPeriod;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class ShuPaymentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->input('search')
        );

        $year = $request->input('year');
        $paymentMethod = $request->input(
            'payment_method'
        );

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $payments = ShuPayment::query()
            ->with([
                'allocation.member:id,member_number,name',
                'allocation.period:id,code,year',
                'user:id,name',
            ])
            ->when(
                $search,
                function ($query) use ($search): void {
                    $query->where(function ($subQuery) use ($search): void {
                        $subQuery
                            ->where(
                                'payment_code',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhere(
                                'reference_number',
                                'like',
                                "%{$search}%"
                            )
                            ->orWhereHas(
                                'allocation.member',
                                function ($memberQuery) use ($search): void {
                                    $memberQuery
                                        ->where(
                                            'name',
                                            'like',
                                            "%{$search}%"
                                        )
                                        ->orWhere(
                                            'member_number',
                                            'like',
                                            "%{$search}%"
                                        );
                                }
                            );
                    });
                }
            )
            ->when(
                $year,
                fn ($query) => $query->whereHas(
                    'allocation.period',
                    fn ($periodQuery) =>
                        $periodQuery->where(
                            'year',
                            $year
                        )
                )
            )
            ->when(
                in_array(
                    $paymentMethod,
                    [
                        'cash',
                        'transfer',
                        'other',
                    ],
                    true
                ),
                fn ($query) => $query->where(
                    'payment_method',
                    $paymentMethod
                )
            )
            ->when(
                $dateFrom,
                fn ($query) => $query->whereDate(
                    'payment_date',
                    '>=',
                    $dateFrom
                )
            )
            ->when(
                $dateTo,
                fn ($query) => $query->whereDate(
                    'payment_date',
                    '<=',
                    $dateTo
                )
            )
            ->latest('payment_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $periodYears = ShuPeriod::query()
            ->orderByDesc('year')
            ->pluck('year');

        $statisticsQuery = ShuPayment::query();

        $statistics = [
            'payment_count' => (clone $statisticsQuery)
                ->count(),

            'total_paid' => (float) (clone $statisticsQuery)
                ->sum('amount'),

            'cash_total' => (float) (clone $statisticsQuery)
                ->where('payment_method', 'cash')
                ->sum('amount'),

            'transfer_total' => (float) (clone $statisticsQuery)
                ->where('payment_method', 'transfer')
                ->sum('amount'),
        ];

        return view(
            'shu-payments.index',
            compact(
                'payments',
                'periodYears',
                'statistics',
                'search',
                'year',
                'paymentMethod',
                'dateFrom',
                'dateTo'
            )
        );
    }

    public function store(
        Request $request,
        ShuMemberAllocation $shuMemberAllocation
    ): RedirectResponse {
        $data = $request->validate(
            $this->paymentRules(),
            $this->paymentMessages()
        );

        try {
            DB::transaction(function () use (
                $shuMemberAllocation,
                $data
            ): void {
                $allocation = ShuMemberAllocation::query()
                    ->with([
                        'period',
                        'member:id,name,member_number',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $shuMemberAllocation->id
                    );

                $this->ensurePeriodCanBePaid(
                    $allocation->period
                );

                $remainingAmount = $this->remainingAmount(
                    $allocation
                );

                if ($remainingAmount <= 0) {
                    throw new DomainException(
                        'SHU anggota ini sudah lunas.'
                    );
                }

                $paymentAmount = round(
                    (float) $data['amount'],
                    2
                );

                if ($paymentAmount > $remainingAmount) {
                    throw new DomainException(
                        sprintf(
                            'Nominal pembayaran melebihi sisa SHU Rp%s.',
                            number_format(
                                $remainingAmount,
                                0,
                                ',',
                                '.'
                            )
                        )
                    );
                }

                $this->createPayment(
                    allocation: $allocation,
                    paymentDate: $data['payment_date'],
                    amount: $paymentAmount,
                    paymentMethod: $data['payment_method'],
                    referenceNumber:
                        $data['reference_number']
                        ?? null,
                    notes: $data['notes'] ?? null
                );

                $this->updatePeriodStatus(
                    $allocation->period
                );
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Pembayaran SHU gagal: '
                    . $exception->getMessage()
                );
        }

        return back()->with(
            'success',
            'Pembayaran SHU berhasil disimpan dan dicatat sebagai kas keluar.'
        );
    }

    public function bulkStore(
        Request $request,
        ShuPeriod $shuPeriod
    ): RedirectResponse {
        $data = $request->validate([
            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'cash',
                    'transfer',
                    'other',
                ]),
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:150',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],

            'confirm_bulk_payment' => [
                'required',
                'accepted',
            ],
        ], [
            'payment_date.required' =>
                'Tanggal pembayaran wajib diisi.',

            'payment_date.before_or_equal' =>
                'Tanggal pembayaran tidak boleh melebihi hari ini.',

            'payment_method.required' =>
                'Metode pembayaran wajib dipilih.',

            'confirm_bulk_payment.accepted' =>
                'Konfirmasi pembayaran massal wajib dicentang.',
        ]);

        try {
            $result = DB::transaction(function () use (
                $shuPeriod,
                $data
            ): array {
                $period = ShuPeriod::query()
                    ->lockForUpdate()
                    ->findOrFail($shuPeriod->id);

                $this->ensurePeriodCanBePaid(
                    $period
                );

                $allocations = ShuMemberAllocation::query()
                    ->with([
                        'member:id,name,member_number',
                    ])
                    ->where(
                        'shu_period_id',
                        $period->id
                    )
                    ->where(
                        'payment_status',
                        '!=',
                        'paid'
                    )
                    ->lockForUpdate()
                    ->orderBy('source_number')
                    ->get();

                if ($allocations->isEmpty()) {
                    throw new DomainException(
                        'Tidak ada anggota yang memiliki sisa pembayaran SHU.'
                    );
                }

                $paymentCount = 0;
                $paymentTotal = 0.0;

                foreach ($allocations as $allocation) {
                    $remainingAmount =
                        $this->remainingAmount(
                            $allocation
                        );

                    if ($remainingAmount <= 0) {
                        continue;
                    }

                    $referenceNumber = $data[
                        'reference_number'
                    ] ?? null;

                    if ($referenceNumber) {
                        $referenceNumber = sprintf(
                            '%s-%s',
                            $referenceNumber,
                            $allocation
                                ->member
                                ->member_number
                        );
                    }

                    $this->createPayment(
                        allocation: $allocation,
                        paymentDate:
                            $data['payment_date'],
                        amount:
                            $remainingAmount,
                        paymentMethod:
                            $data['payment_method'],
                        referenceNumber:
                            $referenceNumber,
                        notes:
                            $data['notes']
                            ?? 'Pembayaran SHU massal.'
                    );

                    $paymentCount++;
                    $paymentTotal += $remainingAmount;
                }

                $this->updatePeriodStatus(
                    $period
                );

                return [
                    'payment_count' => $paymentCount,
                    'payment_total' => round(
                        $paymentTotal,
                        2
                    ),
                ];
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Pembayaran massal gagal: '
                    . $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'shu-periods.show',
                $shuPeriod
            )
            ->with(
                'success',
                sprintf(
                    'Pembayaran massal berhasil. %d anggota dibayar dengan total Rp%s.',
                    $result['payment_count'],
                    number_format(
                        $result['payment_total'],
                        0,
                        ',',
                        '.'
                    )
                )
            );
    }

    public function show(
        ShuPayment $shuPayment
    ): View {
        $shuPayment->load([
            'allocation.member:id,member_number,name,address,phone',
            'allocation.period',
            'user:id,name',
        ]);

        $cashTransaction = CashTransaction::query()
            ->where(
                'source_type',
                'shu_payment'
            )
            ->where(
                'source_id',
                $shuPayment->id
            )
            ->first();

        return view(
            'shu-payments.show',
            compact(
                'shuPayment',
                'cashTransaction'
            )
        );
    }

    public function receipt(
        ShuPayment $shuPayment
    ): View {
        $shuPayment->load([
            'allocation.member:id,member_number,name,address,phone',
            'allocation.period',
            'user:id,name',
        ]);

        $cashTransaction = CashTransaction::query()
            ->where(
                'source_type',
                'shu_payment'
            )
            ->where(
                'source_id',
                $shuPayment->id
            )
            ->first();

        $printMode = true;

        return view(
            'shu-payments.show',
            compact(
                'shuPayment',
                'cashTransaction',
                'printMode'
            )
        );
    }

    private function createPayment(
        ShuMemberAllocation $allocation,
        mixed $paymentDate,
        float $amount,
        string $paymentMethod,
        ?string $referenceNumber,
        ?string $notes
    ): ShuPayment {
        $payment = ShuPayment::create([
            'shu_member_allocation_id' =>
                $allocation->id,

            'payment_date' =>
                $paymentDate,

            'amount' =>
                $amount,

            'payment_method' =>
                $paymentMethod,

            'reference_number' =>
                $referenceNumber,

            'user_id' =>
                auth()->id(),

            'notes' =>
                $notes,
        ]);

        CashTransaction::firstOrCreate(
            [
                'source_type' =>
                    'shu_payment',

                'source_id' =>
                    $payment->id,
            ],
            [
                'transaction_date' =>
                    $payment->payment_date,

                'direction' =>
                    'expense',

                'category' =>
                    'Pembayaran SHU Anggota',

                'amount' =>
                    $amount,

                'payment_method' =>
                    $payment->payment_method,

                'description' => sprintf(
                    'Pembayaran SHU tahun %d kepada %s (%s), nomor pembayaran %s.',
                    $allocation->period->year,
                    $allocation->member->name,
                    $allocation->member->member_number,
                    $payment->payment_code
                ),

                'user_id' =>
                    auth()->id(),
            ]
        );

        $paidAmount = round(
            (float) $allocation->paid_amount
            + $amount,
            2
        );

        $isPaid = $paidAmount
            >= (float) $allocation->total_shu;

        $allocation->update([
            'paid_amount' =>
                $paidAmount,

            'payment_status' =>
                $isPaid
                    ? 'paid'
                    : 'partial',

            'paid_at' =>
                $isPaid
                    ? now()
                    : null,
        ]);

        return $payment;
    }

    private function updatePeriodStatus(
        ShuPeriod $period
    ): void {
        $remainingCount = $period
            ->allocations()
            ->where(
                'payment_status',
                '!=',
                'paid'
            )
            ->count();

        if (
            $remainingCount === 0
            && $period
                ->allocations()
                ->exists()
        ) {
            $period->update([
                'status' => 'distributed',
            ]);
        }
    }

    private function ensurePeriodCanBePaid(
        ShuPeriod $period
    ): void {
        if (
            !in_array(
                $period->status,
                [
                    'approved',
                    'distributed',
                ],
                true
            )
        ) {
            throw new DomainException(
                'Periode SHU belum disetujui.'
            );
        }
    }

    private function remainingAmount(
        ShuMemberAllocation $allocation
    ): float {
        return max(
            round(
                (float) $allocation->total_shu
                - (float) $allocation->paid_amount,
                2
            ),
            0
        );
    }

    private function paymentRules(): array
    {
        return [
            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'amount' => [
                'required',
                'numeric',
                'min:1',
            ],

            'payment_method' => [
                'required',
                Rule::in([
                    'cash',
                    'transfer',
                    'other',
                ]),
            ],

            'reference_number' => [
                'nullable',
                'string',
                'max:150',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    private function paymentMessages(): array
    {
        return [
            'payment_date.required' =>
                'Tanggal pembayaran wajib diisi.',

            'payment_date.before_or_equal' =>
                'Tanggal pembayaran tidak boleh melebihi hari ini.',

            'amount.required' =>
                'Nominal pembayaran wajib diisi.',

            'amount.min' =>
                'Nominal pembayaran minimal Rp1.',

            'payment_method.required' =>
                'Metode pembayaran wajib dipilih.',
        ];
    }
}
