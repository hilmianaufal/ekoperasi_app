<?php

namespace App\Http\Controllers;

use App\Models\CashImportBatch;
use App\Models\ImportBatch;
use App\Services\Imports\CashImportReconciliationService;
use App\Services\Imports\CashMonthlyImportService;
use App\Services\Imports\CashMonthlyParserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Throwable;

class CashImportController extends Controller
{
    public function index(): View
    {
        $dataImportBatches = ImportBatch::query()
            ->whereNotNull('members_savings_imported_at')
            ->whereNotNull('financing_imported_at')
            ->latest()
            ->get([
                'id',
                'code',
                'original_name',
                'cutoff_date',
            ]);

        $batches = CashImportBatch::query()
            ->with([
                'dataImportBatch:id,code,original_name',
                'user:id,name',
            ])
            ->latest()
            ->paginate(10);

        return view(
            'cash-imports.index',
            compact(
                'dataImportBatches',
                'batches'
            )
        );
    }

    public function store(
        Request $request,
        CashMonthlyParserService $parser
    ): RedirectResponse {
        $data = $request->validate([
            'data_import_batch_id' => [
                'required',
                'exists:import_batches,id',
            ],
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
        ]);

        $dataBatch = ImportBatch::query()
            ->findOrFail(
                $data['data_import_batch_id']
            );

        if (
            !$dataBatch->members_savings_imported_at
            || !$dataBatch->financing_imported_at
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Batch utama belum selesai diproses.'
                );
        }

        $file = $request->file('file');

        $fileHash = hash_file(
            'sha256',
            $file->getRealPath()
        );

        $duplicate = CashImportBatch::query()
            ->where(
                'data_import_batch_id',
                $dataBatch->id
            )
            ->where('file_hash', $fileHash)
            ->first();

        if ($duplicate) {
            return redirect()
                ->route(
                    'cash-imports.show',
                    $duplicate
                )
                ->with(
                    'error',
                    'File kas yang sama sudah pernah diunggah.'
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
            'cash-imports',
            $storedName,
            'local'
        );

        $batch = CashImportBatch::create([
            'data_import_batch_id' => $dataBatch->id,
            'original_name'
                => $file->getClientOriginalName(),

            'stored_path' => $storedPath,
            'file_hash' => $fileHash,
            'cutoff_date' => $data['cutoff_date'],
            'status' => 'uploaded',
            'user_id' => auth()->id(),
        ]);

        try {
            $parser->parse($batch);
        } catch (Throwable $exception) {
            report($exception);

            $batch->update([
                'status' => 'failed',
                'error_message'
                    => $exception->getMessage(),
            ]);

            return redirect()
                ->route(
                    'cash-imports.show',
                    $batch
                )
                ->with(
                    'error',
                    'File kas gagal dibaca: '
                    . $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'cash-imports.show',
                $batch
            )
            ->with(
                'success',
                'File kas berhasil dibaca. Silakan periksa preview.'
            );
    }

    public function show(
        CashImportBatch $cashImportBatch,
        CashImportReconciliationService $reconciliationService
    ): View {
        $cashImportBatch->load([
            'dataImportBatch:id,code,original_name,cutoff_date',
            'user:id,name',
        ]);

        $monthlySummaries = $cashImportBatch
            ->rows()
            ->select([
                'sheet_name',
                'period_date',
            ])
            ->selectRaw(
                'COUNT(*) AS source_rows'
            )
            ->selectRaw(
                'SUM(financing_expense) AS financing_expense'
            )
            ->selectRaw(
                'SUM(voluntary_withdrawal) AS voluntary_withdrawal'
            )
            ->selectRaw(
                'SUM(transport_expense) AS transport_expense'
            )
            ->selectRaw(
                'SUM(other_expense) AS other_expense'
            )
            ->selectRaw(
                'SUM(installment_income) AS installment_income'
            )
            ->selectRaw(
                'SUM(profit_share_income) AS profit_share_income'
            )
            ->selectRaw(
                'SUM(administration_income) AS administration_income'
            )
            ->selectRaw(
                'SUM(principal_deposit) AS principal_deposit'
            )
            ->selectRaw(
                'SUM(mandatory_deposit) AS mandatory_deposit'
            )
            ->selectRaw(
                'SUM(voluntary_deposit) AS voluntary_deposit'
            )
            ->groupBy([
                'sheet_name',
                'period_date',
            ])
            ->orderBy('period_date')
            ->get();

        $reconciliation = $cashImportBatch
            ->processed_at
                ? $reconciliationService->build(
                    $cashImportBatch
                )
                : null;

        return view(
            'cash-imports.show',
            compact(
                'cashImportBatch',
                'monthlySummaries',
                'reconciliation'
            )
        );
    }

    public function process(
        CashImportBatch $cashImportBatch,
        CashMonthlyImportService $importService
    ): RedirectResponse {
        try {
            $result = $importService->import(
                $cashImportBatch,
                auth()->id()
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Import kas gagal diproses: '
                . $exception->getMessage()
            );
        }

        return back()->with(
            'success',
            sprintf(
                'Import kas berhasil. %d transaksi kas dibuat.',
                $result['created_count']
            )
        );
    }

    public function destroy(
        CashImportBatch $cashImportBatch
    ): RedirectResponse {
        if ($cashImportBatch->processed_at) {
            return back()->with(
                'error',
                'Batch kas yang sudah diproses tidak dapat dihapus.'
            );
        }

        Storage::disk('local')
            ->delete(
                $cashImportBatch->stored_path
            );

        $cashImportBatch->delete();

        return redirect()
            ->route('cash-imports.index')
            ->with(
                'success',
                'Batch kas berhasil dihapus.'
            );
    }
}
