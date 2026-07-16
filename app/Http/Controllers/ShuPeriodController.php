<?php

namespace App\Http\Controllers;

use App\Models\ShuPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ShuPeriodController extends Controller
{
    public function index(): View
    {
        $periods = ShuPeriod::query()
            ->withCount([
                'allocations',
                'importBatches',
            ])
            ->withSum(
                'allocations',
                'total_shu'
            )
            ->latest('year')
            ->paginate(10);

        $statistics = [
            'period_count' => ShuPeriod::count(),

            'draft_count' => ShuPeriod::query()
                ->whereIn('status', [
                    'draft',
                    'review',
                ])
                ->count(),

            'distributed_total' => (float) ShuPeriod::query()
                ->where('status', 'distributed')
                ->sum('declared_member_shu'),
        ];

        return view(
            'shu-periods.index',
            compact(
                'periods',
                'statistics'
            )
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $data = $request->validate([
            'year' => [
                'required',
                'integer',
                'min:2000',
                'max:' . (now()->year + 1),
                Rule::unique('shu_periods', 'year'),
            ],

            'calculation_date' => [
                'required',
                'date',
            ],

            'business_service_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'saving_service_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'declared_total_shu' => [
                'required',
                'numeric',
                'min:0',
            ],

            'declared_member_shu' => [
                'required',
                'numeric',
                'min:0',
            ],

            'declared_business_service' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'declared_saving_service' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:3000',
            ],
        ]);

        ShuPeriod::create([
            ...$data,

            'declared_business_service' =>
            $data['declared_business_service']
                ?? 0,

            'declared_saving_service' =>
            $data['declared_saving_service']
                ?? 0,

            'status' => 'draft',
        ]);

        return redirect()
            ->route('shu-periods.index')
            ->with(
                'success',
                'Periode SHU berhasil dibuat.'
            );
    }

    public function show(
        ShuPeriod $shuPeriod
    ): View {
        $shuPeriod->load([
            'approver:id,name',
        ]);

        $importBatches = $shuPeriod
            ->importBatches()
            ->with('user:id,name')
            ->latest()
            ->get();

        $allocations = $shuPeriod
            ->allocations()
            ->with([
                'member:id,member_number,name',
                'payments' => fn($query) =>
                $query
                    ->latest('payment_date')
                    ->latest('id'),
            ])
            ->orderBy('source_number')
            ->paginate(20);

        $allocatedTotal = (float) $shuPeriod
            ->allocations()
            ->sum('total_shu');

        $paidTotal = (float) $shuPeriod
            ->allocations()
            ->sum('paid_amount');

        $summary = [
            'member_count' => $shuPeriod
                ->allocations()
                ->count(),

            'business_service' => (float) $shuPeriod
                ->allocations()
                ->sum(
                    'business_service_amount'
                ),

            'saving_service' => (float) $shuPeriod
                ->allocations()
                ->sum(
                    'saving_service_amount'
                ),

            'allocated_total' =>
            $allocatedTotal,

            'paid_total' =>
            $paidTotal,

            'remaining_total' => max(
                $allocatedTotal - $paidTotal,
                0
            ),

            'difference' => round(
                (float) $shuPeriod
                    ->declared_member_shu
                    - $allocatedTotal,
                2
            ),

            'unpaid_count' => $shuPeriod
                ->allocations()
                ->where(
                    'payment_status',
                    '!=',
                    'paid'
                )
                ->count(),
        ];

        return view(
            'shu-periods.show',
            compact(
                'shuPeriod',
                'importBatches',
                'allocations',
                'summary'
            )
        );
    }
    public function approve(
        Request $request,
        ShuPeriod $shuPeriod
    ): RedirectResponse {
        if (
            !in_array(
                $shuPeriod->status,
                ['draft', 'review'],
                true
            )
        ) {
            return back()->with(
                'error',
                'Periode SHU ini sudah disetujui atau dibagikan.'
            );
        }

        $allocationCount = $shuPeriod
            ->allocations()
            ->count();

        if ($allocationCount === 0) {
            return back()->with(
                'error',
                'Periode belum memiliki alokasi SHU anggota.'
            );
        }

        $allocatedTotal = (float) $shuPeriod
            ->allocations()
            ->sum('total_shu');

        $declaredTotal = (float)
        $shuPeriod->declared_member_shu;

        $difference = round(
            $declaredTotal - $allocatedTotal,
            2
        );

        $data = $request->validate([
            'approval_notes' => [
                'nullable',
                'string',
                'max:3000',
            ],

            'acknowledge_difference' => [
                'nullable',
                'accepted',
            ],
        ], [
            'acknowledge_difference.accepted' =>
            'Selisih alokasi harus dikonfirmasi sebelum periode disetujui.',
        ]);

        if (
            abs($difference) >= 0.01
            && !$request->boolean(
                'acknowledge_difference'
            )
        ) {
            throw ValidationException::withMessages([
                'acknowledge_difference' =>
                sprintf(
                    'Terdapat selisih Rp%s. Centang konfirmasi selisih untuk menyetujui periode.',
                    number_format(
                        abs($difference),
                        0,
                        ',',
                        '.'
                    )
                ),
            ]);
        }

        DB::transaction(function () use (
            $shuPeriod,
            $data,
            $difference,
            $allocatedTotal
        ): void {
            $existingNotes = trim(
                (string) $shuPeriod->notes
            );

            $approvalNotes = trim(
                (string) (
                    $data['approval_notes']
                    ?? ''
                )
            );

            $auditNote = sprintf(
                'Disetujui pada %s. Total alokasi Rp%s, selisih terhadap ketetapan Rp%s.',
                now()->translatedFormat(
                    'd F Y H:i'
                ),
                number_format(
                    $allocatedTotal,
                    0,
                    ',',
                    '.'
                ),
                number_format(
                    $difference,
                    0,
                    ',',
                    '.'
                )
            );

            $notes = collect([
                $existingNotes,
                $approvalNotes,
                $auditNote,
            ])
                ->filter()
                ->implode("\n\n");

            $shuPeriod->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'notes' => $notes,
            ]);
        });

        return back()->with(
            'success',
            'Periode SHU berhasil disetujui dan siap dibayarkan.'
        );
    }
    public function destroy(
        ShuPeriod $shuPeriod
    ): RedirectResponse {
        if ($shuPeriod->status !== 'draft') {
            return back()->with(
                'error',
                'Periode SHU yang sudah diproses tidak dapat dihapus.'
            );
        }

        if (
            $shuPeriod->allocations()->exists()
            || $shuPeriod->importBatches()->exists()
        ) {
            return back()->with(
                'error',
                'Periode memiliki data import atau alokasi anggota.'
            );
        }

        $shuPeriod->delete();

        return redirect()
            ->route('shu-periods.index')
            ->with(
                'success',
                'Periode SHU berhasil dihapus.'
            );
    }
}
