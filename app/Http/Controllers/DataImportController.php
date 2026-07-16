<?php

namespace App\Http\Controllers;

use App\Models\ImportBatch;
use App\Services\Imports\ImportReconciliationService;
use App\Services\Imports\LegacyFinancingImportService;
use App\Services\Imports\MembersSavingsImportService;
use App\Services\Imports\RekapAngsuranImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class DataImportController extends Controller
{
    public function index(): View
    {
        $batches = ImportBatch::query()
            ->with('user:id,name')
            ->latest()
            ->paginate(10);

        return view(
            'data-imports.index',
            compact('batches')
        );
    }

    public function store(
        Request $request,
        RekapAngsuranImportService $importService
    ): RedirectResponse {
        $data = $request->validate([
            'file' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
            'cutoff_date' => [
                'required',
                'date',
            ],
        ], [
            'file.required' => 'File Excel wajib dipilih.',
            'file.mimes' => 'File harus berformat XLSX atau XLS.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
            'cutoff_date.required' => 'Tanggal cut-off wajib diisi.',
        ]);

        $file = $request->file('file');

        $fileHash = hash_file(
            'sha256',
            $file->getRealPath()
        );

        $existingBatch = ImportBatch::query()
            ->where('file_hash', $fileHash)
            ->whereIn('status', [
                'previewed',
                'processing',
                'completed',
            ])
            ->latest()
            ->first();

        if ($existingBatch) {
            return redirect()
                ->route(
                    'data-imports.show',
                    $existingBatch
                )
                ->with(
                    'error',
                    'File yang sama sudah pernah diunggah. Silakan periksa batch sebelumnya.'
                );
        }

        $storedName = sprintf(
            '%s-%s.%s',
            now()->format('YmdHis'),
            bin2hex(random_bytes(4)),
            strtolower($file->getClientOriginalExtension())
        );

        $storedPath = $file->storeAs(
            'imports',
            $storedName,
            'local'
        );

        $batch = ImportBatch::create([
            'type' => 'installment_recap',
            'original_name' => $file->getClientOriginalName(),
            'stored_path' => $storedPath,
            'file_hash' => $fileHash,
            'cutoff_date' => $data['cutoff_date'],
            'status' => 'uploaded',
            'user_id' => auth()->id(),
        ]);

        try {
            $importService->parse($batch);
        } catch (Throwable $exception) {
            report($exception);

            $batch->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
            ]);

            return redirect()
                ->route('data-imports.show', $batch)
                ->with(
                    'error',
                    'File belum dapat dibaca: '
                        . $exception->getMessage()
                );
        }

        return redirect()
            ->route('data-imports.show', $batch)
            ->with(
                'success',
                'File berhasil dibaca. Silakan periksa hasil preview sebelum melakukan import.'
            );
    }

    public function show(
        ImportBatch $importBatch
    ): View {
        $importBatch->load([
            'user:id,name',
            'mappings' => fn($query) => $query
                ->with('member:id,member_number,name')
                ->orderBy('source_number'),
        ]);

        $sheetSummaries = $importBatch
            ->rows()
            ->select([
                'sheet_name',
                'period_date',
            ])
            ->selectRaw('COUNT(*) AS member_rows')
            ->selectRaw('SUM(principal_saving) AS principal_saving')
            ->selectRaw('SUM(mandatory_saving) AS mandatory_saving')
            ->selectRaw('SUM(principal_installment) AS principal_installment')
            ->selectRaw('SUM(profit_share) AS profit_share')
            ->selectRaw('SUM(voluntary_saving) AS voluntary_saving')
            ->selectRaw('SUM(voluntary_withdrawal) AS voluntary_withdrawal')
            ->selectRaw('SUM(administration_fee) AS administration_fee')
            ->selectRaw('SUM(new_financing) AS new_financing')
            ->groupBy([
                'sheet_name',
                'period_date',
            ])
            ->orderBy('period_date')
            ->get();

        $latestBalances = $importBatch
            ->rows()
            ->whereDate(
                'period_date',
                $importBatch->cutoff_date
            )
            ->selectRaw(
                'COALESCE(SUM(mandatory_balance), 0) AS mandatory_balance'
            )
            ->selectRaw(
                'COALESCE(SUM(voluntary_balance), 0) AS voluntary_balance'
            )
            ->selectRaw(
                'COALESCE(SUM(remaining_financing), 0) AS remaining_financing'
            )
            ->first();

        return view(
            'data-imports.show',
            compact(
                'importBatch',
                'sheetSummaries',
                'latestBalances'
            )
        );
    }

    public function updateMappings(
        Request $request,
        ImportBatch $importBatch,
        RekapAngsuranImportService $importService
    ): RedirectResponse {
        if (
            in_array(
                $importBatch->status,
                ['processing', 'completed'],
                true
            )
            || $importBatch->members_savings_imported_at
        ) {
            return back()->with(
                'error',
                'Mapping tidak dapat diubah karena anggota dan simpanan sudah diimpor.'
            );
        }

        $data = $request->validate([
            'mappings' => [
                'required',
                'array',
            ],
            'mappings.*.canonical_name' => [
                'required',
                'string',
                'max:150',
            ],
            'mappings.*.status' => [
                'required',
                'in:new,matched,review,ignored',
            ],
        ], [
            'mappings.*.canonical_name.required'
            => 'Nama anggota tidak boleh kosong.',
        ]);

        DB::transaction(function () use (
            $data,
            $importBatch,
            $importService
        ): void {
            foreach ($data['mappings'] as $mappingId => $mappingData) {
                $mapping = $importBatch
                    ->mappings()
                    ->findOrFail($mappingId);

                $canonicalName = trim(
                    $mappingData['canonical_name']
                );

                $mapping->update([
                    'canonical_name' => $canonicalName,
                    'normalized_name' => $importService
                        ->normalizeName($canonicalName),
                    'status' => $mappingData['status'],
                ]);

                $importBatch
                    ->rows()
                    ->where(
                        'source_number',
                        $mapping->source_number
                    )
                    ->update([
                        'canonical_name' => $canonicalName,
                        'status' => $mappingData['status'] === 'ignored'
                            ? 'skipped'
                            : 'ready',
                    ]);
            }
        });

        return back()->with(
            'success',
            'Mapping nama anggota berhasil diperbarui.'
        );
    }

    public function processFinancing(
        ImportBatch $importBatch,
        LegacyFinancingImportService $importService
    ): RedirectResponse {
        try {
            $result = $importService->import(
                $importBatch,
                auth()->id()
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Import pembiayaan belum dapat diproses: '
                    . $exception->getMessage()
            );
        }

        return back()->with(
            'success',
            sprintf(
                'Import pembiayaan berhasil. %d pembiayaan, %d angsuran, %d pembayaran, dan %d catatan audit dibuat.',
                $result['loan_count'],
                $result['installment_count'],
                $result['payment_count'],
                $result['entry_count']
            )
        );
    }


    public function destroy(
        ImportBatch $importBatch
    ): RedirectResponse {
        if (
            $importBatch->status === 'completed'
            || $importBatch->members_savings_imported_at
            || $importBatch->financing_imported_at
        ) {
            return back()->with(
                'error',
                'Batch yang sudah menghasilkan data anggota atau transaksi tidak dapat dihapus.'
            );
        }

        Storage::disk('local')
            ->delete($importBatch->stored_path);

        $importBatch->delete();

        return redirect()
            ->route('data-imports.index')
            ->with(
                'success',
                'Batch import berhasil dihapus.'
            );
    }
    public function processMembersSavings(
        ImportBatch $importBatch,
        MembersSavingsImportService $importService
    ): RedirectResponse {
        try {
            $result = $importService->import(
                $importBatch,
                auth()->id()
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Import belum dapat diproses: '
                    . $exception->getMessage()
            );
        }

        return back()->with(
            'success',
            sprintf(
                'Import berhasil. %d anggota diproses, %d anggota baru dibuat, dan %d transaksi simpanan dimasukkan.',
                $result['member_count'],
                $result['new_member_count'],
                $result['transaction_count']
            )
        );
    }

    public function reconciliation(
        ImportBatch $importBatch,
        ImportReconciliationService $reconciliationService
    ): View {
        try {
            $reconciliation = $reconciliationService->build(
                $importBatch
            );
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route(
                    'data-imports.show',
                    $importBatch
                )
                ->with(
                    'error',
                    'Rekonsiliasi belum dapat ditampilkan: '
                        . $exception->getMessage()
                );
        }

        return view(
            'data-imports.reconciliation',
            compact(
                'importBatch',
                'reconciliation'
            )
        );
    }
}
