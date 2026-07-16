<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\ShuImportBatch;
use App\Models\ShuPeriod;
use App\Services\Imports\ShuAllocationImportService;
use App\Services\Imports\ShuImportParserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class ShuImportController extends Controller
{
    public function store(
        Request $request,
        ShuPeriod $shuPeriod,
        ShuImportParserService $parser
    ): RedirectResponse {
        if (
            in_array(
                $shuPeriod->status,
                ['approved', 'distributed'],
                true
            )
        ) {
            return back()->with(
                'error',
                'Periode SHU sudah disetujui atau dibagikan.'
            );
        }

        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ], [
            'file.required' => 'File SHU wajib dipilih.',
            'file.mimes' => 'File harus berformat XLSX atau XLS.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $request->file('file');

        $fileHash = hash_file(
            'sha256',
            $file->getRealPath()
        );

        $duplicate = ShuImportBatch::query()
            ->where(
                'shu_period_id',
                $shuPeriod->id
            )
            ->where('file_hash', $fileHash)
            ->first();

        if ($duplicate) {
            return redirect()
                ->route(
                    'shu-imports.show',
                    $duplicate
                )
                ->with(
                    'error',
                    'File SHU yang sama sudah pernah diunggah.'
                );
        }

        $storedName = sprintf(
            '%s-%s.%s',
            now()->format('YmdHis'),
            bin2hex(random_bytes(4)),
            strtolower(
                $file->getClientOriginalExtension()
            )
        );

        $storedPath = $file->storeAs(
            'shu-imports',
            $storedName,
            'local'
        );

        $batch = ShuImportBatch::create([
            'shu_period_id' => $shuPeriod->id,
            'original_name' =>
                $file->getClientOriginalName(),

            'stored_path' => $storedPath,
            'file_hash' => $fileHash,
            'status' => 'uploaded',
            'user_id' => auth()->id(),
        ]);

        try {
            $parser->parse($batch);
        } catch (Throwable $exception) {
            report($exception);

            $batch->update([
                'status' => 'failed',
                'error_message' =>
                    $exception->getMessage(),
            ]);

            return redirect()
                ->route(
                    'shu-imports.show',
                    $batch
                )
                ->with(
                    'error',
                    'File SHU gagal dibaca: '
                    . $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'shu-imports.show',
                $batch
            )
            ->with(
                'success',
                'File SHU berhasil dibaca. Periksa mapping dan selisih perhitungannya.'
            );
    }

    public function show(
        ShuImportBatch $shuImportBatch
    ): View {
        $shuImportBatch->load([
            'period',
            'user:id,name',
            'rows' => fn ($query) => $query
                ->with(
                    'member:id,member_number,name'
                )
                ->orderBy('source_number')
                ->orderBy('row_number'),
        ]);

        $members = Member::query()
            ->orderBy('name')
            ->get([
                'id',
                'member_number',
                'name',
            ]);

        $summary = [
            'source_business_service' =>
                (float) $shuImportBatch
                    ->rows
                    ->sum('source_business_service'),

            'source_saving_service' =>
                (float) $shuImportBatch
                    ->rows
                    ->sum('source_saving_service'),

            'source_total_shu' =>
                (float) $shuImportBatch
                    ->rows
                    ->sum('source_total_shu'),

            'calculated_business_service' =>
                (float) $shuImportBatch
                    ->rows
                    ->sum(
                        'calculated_business_service'
                    ),

            'calculated_saving_service' =>
                (float) $shuImportBatch
                    ->rows
                    ->sum(
                        'calculated_saving_service'
                    ),

            'calculated_total_shu' =>
                (float) $shuImportBatch
                    ->rows
                    ->sum('calculated_total_shu'),

            'difference' =>
                (float) $shuImportBatch
                    ->rows
                    ->sum('difference'),
        ];

        return view(
            'shu-imports.show',
            compact(
                'shuImportBatch',
                'members',
                'summary'
            )
        );
    }

    public function updateRows(
        Request $request,
        ShuImportBatch $shuImportBatch
    ): RedirectResponse {
        if ($shuImportBatch->processed_at) {
            return back()->with(
                'error',
                'Mapping tidak dapat diubah karena batch sudah diproses.'
            );
        }

        $data = $request->validate([
            'rows' => [
                'required',
                'array',
            ],

            'rows.*.member_id' => [
                'nullable',
                'exists:members,id',
            ],

            'rows.*.status' => [
                'required',
                'in:matched,review,ignored',
            ],
        ]);

        DB::transaction(function () use (
            $data,
            $shuImportBatch
        ): void {
            foreach (
                $data['rows']
                as $rowId => $rowData
            ) {
                $row = $shuImportBatch
                    ->rows()
                    ->findOrFail($rowId);

                $status = $rowData['status'];
                $memberId = $rowData['member_id']
                    ?: null;

                if (
                    $status === 'matched'
                    && !$memberId
                ) {
                    $status = 'review';
                }

                if ($status === 'ignored') {
                    $memberId = null;
                }

                $row->update([
                    'member_id' => $memberId,
                    'status' => $status,
                ]);
            }

            $shuImportBatch->update([
                'matched_count' =>
                    $shuImportBatch
                        ->rows()
                        ->where(
                            'status',
                            'matched'
                        )
                        ->whereNotNull(
                            'member_id'
                        )
                        ->count(),

                'review_count' =>
                    $shuImportBatch
                        ->rows()
                        ->whereIn(
                            'status',
                            ['new', 'review']
                        )
                        ->count(),
            ]);
        });

        return back()->with(
            'success',
            'Mapping anggota SHU berhasil disimpan.'
        );
    }

    public function process(
        ShuImportBatch $shuImportBatch,
        ShuAllocationImportService $importService
    ): RedirectResponse {
        try {
            $result = $importService->import(
                $shuImportBatch
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Alokasi SHU gagal diproses: '
                . $exception->getMessage()
            );
        }

        return redirect()
            ->route(
                'shu-periods.show',
                $shuImportBatch->shu_period_id
            )
            ->with(
                'success',
                sprintf(
                    'Alokasi SHU berhasil. %d anggota dengan total Rp%s telah dimasukkan.',
                    $result['allocation_count'],
                    number_format(
                        $result['shu_total'],
                        0,
                        ',',
                        '.'
                    )
                )
            );
    }

    public function destroy(
        ShuImportBatch $shuImportBatch
    ): RedirectResponse {
        if ($shuImportBatch->processed_at) {
            return back()->with(
                'error',
                'Batch SHU yang sudah diproses tidak dapat dihapus.'
            );
        }

        Storage::disk('local')
            ->delete(
                $shuImportBatch->stored_path
            );

        $periodId =
            $shuImportBatch->shu_period_id;

        $shuImportBatch->delete();

        return redirect()
            ->route(
                'shu-periods.show',
                $periodId
            )
            ->with(
                'success',
                'Batch import SHU berhasil dihapus.'
            );
    }
}
