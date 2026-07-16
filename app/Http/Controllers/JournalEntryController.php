<?php

namespace App\Http\Controllers;

use App\Models\AccountingAccount;
use App\Models\JournalEntry;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class JournalEntryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim(
            (string) $request->input('search')
        );

        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $entries = JournalEntry::query()
            ->with([
                'lines:id,journal_entry_id,debit,credit',
                'creator:id,name',
                'poster:id,name',
            ])
            ->when(
                $search,
                function ($query) use ($search): void {
                    $query->where(
                        function ($subQuery) use ($search): void {
                            $subQuery
                                ->where(
                                    'entry_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'reference_number',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'description',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            )
            ->when(
                in_array(
                    $status,
                    [
                        'draft',
                        'posted',
                        'reversed',
                    ],
                    true
                ),
                fn ($query) => $query->where(
                    'status',
                    $status
                )
            )
            ->when(
                $dateFrom,
                fn ($query) => $query->whereDate(
                    'entry_date',
                    '>=',
                    $dateFrom
                )
            )
            ->when(
                $dateTo,
                fn ($query) => $query->whereDate(
                    'entry_date',
                    '<=',
                    $dateTo
                )
            )
            ->latest('entry_date')
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        $statistics = [
            'draft_count' => JournalEntry::query()
                ->where('status', 'draft')
                ->count(),

            'posted_count' => JournalEntry::query()
                ->where('status', 'posted')
                ->count(),

            'reversed_count' => JournalEntry::query()
                ->where('status', 'reversed')
                ->count(),

            'posted_debit' => (float) DB::table(
                'journal_entry_lines'
            )
                ->join(
                    'journal_entries',
                    'journal_entries.id',
                    '=',
                    'journal_entry_lines.journal_entry_id'
                )
                ->where(
                    'journal_entries.status',
                    'posted'
                )
                ->sum(
                    'journal_entry_lines.debit'
                ),
        ];

        return view(
            'journal-entries.index',
            compact(
                'entries',
                'statistics',
                'search',
                'status',
                'dateFrom',
                'dateTo'
            )
        );
    }

    public function create(): View
    {
        $accounts = AccountingAccount::query()
            ->where('is_header', false)
            ->where('is_active', true)
            ->where(
                'allow_manual_entries',
                true
            )
            ->orderBy('code')
            ->get([
                'id',
                'code',
                'name',
                'type',
            ]);

        return view(
            'journal-entries.create',
            compact('accounts')
        );
    }

    public function store(
        Request $request
    ): RedirectResponse {
        $data = $request->validate(
            [
                'entry_date' => [
                    'required',
                    'date',
                    'before_or_equal:today',
                ],

                'reference_number' => [
                    'nullable',
                    'string',
                    'max:150',
                ],

                'description' => [
                    'required',
                    'string',
                    'max:2000',
                ],

                'notes' => [
                    'nullable',
                    'string',
                    'max:3000',
                ],

                'lines' => [
                    'required',
                    'array',
                    'min:2',
                ],

                'lines.*.accounting_account_id' => [
                    'required',
                    Rule::exists(
                        'accounting_accounts',
                        'id'
                    )->where(
                        fn ($query) => $query
                            ->where(
                                'is_header',
                                false
                            )
                            ->where(
                                'is_active',
                                true
                            )
                            ->where(
                                'allow_manual_entries',
                                true
                            )
                    ),
                ],

                'lines.*.description' => [
                    'nullable',
                    'string',
                    'max:500',
                ],

                'lines.*.debit' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'lines.*.credit' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],
            ],
            [
                'entry_date.required' =>
                    'Tanggal jurnal wajib diisi.',

                'entry_date.before_or_equal' =>
                    'Tanggal jurnal tidak boleh melebihi hari ini.',

                'description.required' =>
                    'Keterangan jurnal wajib diisi.',

                'lines.required' =>
                    'Detail jurnal wajib diisi.',

                'lines.min' =>
                    'Jurnal minimal memiliki dua baris.',

                'lines.*.accounting_account_id.required' =>
                    'Akun pada setiap baris wajib dipilih.',
            ]
        );

        $lines = collect($data['lines'])
            ->map(function (array $line): array {
                return [
                    'accounting_account_id' =>
                        (int) $line[
                            'accounting_account_id'
                        ],

                    'description' => trim(
                        (string) (
                            $line['description']
                            ?? ''
                        )
                    ) ?: null,

                    'debit' => round(
                        (float) (
                            $line['debit']
                            ?? 0
                        ),
                        2
                    ),

                    'credit' => round(
                        (float) (
                            $line['credit']
                            ?? 0
                        ),
                        2
                    ),
                ];
            });

        foreach (
            $lines as $index => $line
        ) {
            $hasDebit = $line['debit'] > 0;
            $hasCredit = $line['credit'] > 0;

            if (
                (!$hasDebit && !$hasCredit)
                || ($hasDebit && $hasCredit)
            ) {
                throw ValidationException::withMessages([
                    "lines.{$index}.debit" =>
                        'Setiap baris harus memiliki nilai debit atau kredit, bukan keduanya.',
                ]);
            }
        }

        $totalDebit = round(
            (float) $lines->sum('debit'),
            2
        );

        $totalCredit = round(
            (float) $lines->sum('credit'),
            2
        );

        if ($totalDebit <= 0) {
            throw ValidationException::withMessages([
                'lines' =>
                    'Total jurnal harus lebih besar dari nol.',
            ]);
        }

        if (
            abs(
                $totalDebit - $totalCredit
            ) >= 0.01
        ) {
            throw ValidationException::withMessages([
                'lines' => sprintf(
                    'Jurnal belum seimbang. Total debit Rp%s dan total kredit Rp%s.',
                    number_format(
                        $totalDebit,
                        0,
                        ',',
                        '.'
                    ),
                    number_format(
                        $totalCredit,
                        0,
                        ',',
                        '.'
                    )
                ),
            ]);
        }

        try {
            $entry = DB::transaction(
                function () use (
                    $data,
                    $lines
                ): JournalEntry {
                    $entry = JournalEntry::create([
                        'entry_date' =>
                            $data['entry_date'],

                        'reference_number' =>
                            $data[
                                'reference_number'
                            ] ?? null,

                        'description' =>
                            $data['description'],

                        'source_type' => null,
                        'source_id' => null,

                        'status' => 'draft',

                        'created_by' =>
                            auth()->id(),

                        'notes' =>
                            $data['notes']
                            ?? null,
                    ]);

                    foreach ($lines as $line) {
                        $entry->lines()->create(
                            $line
                        );
                    }

                    return $entry;
                }
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Jurnal gagal disimpan: '
                    . $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'journal-entries.show',
                $entry
            )
            ->with(
                'success',
                'Jurnal berhasil disimpan sebagai draft.'
            );
    }

    public function show(
        JournalEntry $journalEntry
    ): View {
        $journalEntry->load([
            'lines.account:id,code,name,type,normal_balance',
            'lines.member:id,member_number,name',
            'lines.loan:id,loan_number',
            'creator:id,name',
            'poster:id,name',
            'reversalOf:id,entry_number',
        ]);

        $reversalEntry = JournalEntry::query()
            ->where(
                'reversal_of_id',
                $journalEntry->id
            )
            ->first([
                'id',
                'entry_number',
                'entry_date',
            ]);

        return view(
            'journal-entries.show',
            compact(
                'journalEntry',
                'reversalEntry'
            )
        );
    }

    public function post(
        JournalEntry $journalEntry
    ): RedirectResponse {
        try {
            DB::transaction(
                function () use (
                    $journalEntry
                ): void {
                    $entry = JournalEntry::query()
                        ->with('lines')
                        ->lockForUpdate()
                        ->findOrFail(
                            $journalEntry->id
                        );

                    if (
                        $entry->status !== 'draft'
                    ) {
                        throw new DomainException(
                            'Hanya jurnal draft yang dapat diposting.'
                        );
                    }

                    if (
                        $entry->lines->count() < 2
                    ) {
                        throw new DomainException(
                            'Jurnal minimal memiliki dua baris.'
                        );
                    }

                    if (!$entry->is_balanced) {
                        throw new DomainException(
                            sprintf(
                                'Jurnal belum seimbang. Debit Rp%s dan kredit Rp%s.',
                                number_format(
                                    $entry->total_debit,
                                    0,
                                    ',',
                                    '.'
                                ),
                                number_format(
                                    $entry->total_credit,
                                    0,
                                    ',',
                                    '.'
                                )
                            )
                        );
                    }

                    if (
                        $entry->total_debit <= 0
                    ) {
                        throw new DomainException(
                            'Nominal jurnal harus lebih besar dari nol.'
                        );
                    }

                    $entry->update([
                        'status' => 'posted',
                        'posted_by' =>
                            auth()->id(),

                        'posted_at' => now(),
                    ]);
                }
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()->with(
                'error',
                'Jurnal gagal diposting: '
                . $exception->getMessage()
            );
        }

        return back()->with(
            'success',
            'Jurnal berhasil diposting ke buku besar.'
        );
    }

    public function reverse(
        Request $request,
        JournalEntry $journalEntry
    ): RedirectResponse {
        $data = $request->validate([
            'entry_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ], [
            'entry_date.required' =>
                'Tanggal pembalikan wajib diisi.',

            'entry_date.before_or_equal' =>
                'Tanggal pembalikan tidak boleh melebihi hari ini.',

            'reason.required' =>
                'Alasan pembalikan wajib diisi.',
        ]);

        try {
            $reversal = DB::transaction(
                function () use (
                    $journalEntry,
                    $data
                ): JournalEntry {
                    $entry = JournalEntry::query()
                        ->with('lines')
                        ->lockForUpdate()
                        ->findOrFail(
                            $journalEntry->id
                        );

                    if (
                        $entry->status !== 'posted'
                    ) {
                        throw new DomainException(
                            'Hanya jurnal yang sudah diposting yang dapat dibalik.'
                        );
                    }

                    $alreadyReversed =
                        JournalEntry::query()
                            ->where(
                                'reversal_of_id',
                                $entry->id
                            )
                            ->exists();

                    if ($alreadyReversed) {
                        throw new DomainException(
                            'Jurnal ini sudah memiliki jurnal pembalik.'
                        );
                    }

                    $reversal = JournalEntry::create([
                        'entry_date' =>
                            $data['entry_date'],

                        'reference_number' =>
                            'REV-'
                            . $entry->entry_number,

                        'description' =>
                            'Pembalikan jurnal '
                            . $entry->entry_number,

                        'source_type' =>
                            'journal_reversal',

                        'source_id' =>
                            $entry->id,

                        'status' => 'posted',

                        'reversal_of_id' =>
                            $entry->id,

                        'created_by' =>
                            auth()->id(),

                        'posted_by' =>
                            auth()->id(),

                        'posted_at' => now(),

                        'notes' =>
                            $data['reason'],
                    ]);

                    foreach (
                        $entry->lines as $line
                    ) {
                        $reversal->lines()->create([
                            'accounting_account_id' =>
                                $line
                                    ->accounting_account_id,

                            'description' =>
                                'Pembalikan: '
                                . (
                                    $line->description
                                    ?: $entry->description
                                ),

                            'debit' =>
                                (float) $line->credit,

                            'credit' =>
                                (float) $line->debit,

                            'member_id' =>
                                $line->member_id,

                            'loan_id' =>
                                $line->loan_id,
                        ]);
                    }

                    $entry->update([
                        'status' => 'reversed',
                    ]);

                    return $reversal;
                }
            );
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Jurnal gagal dibalik: '
                    . $exception->getMessage()
                );
        }

        return redirect()
            ->route(
                'journal-entries.show',
                $reversal
            )
            ->with(
                'success',
                'Jurnal pembalik berhasil dibuat dan langsung diposting.'
            );
    }

    public function destroy(
        JournalEntry $journalEntry
    ): RedirectResponse {
        if (
            $journalEntry->status !== 'draft'
        ) {
            return back()->with(
                'error',
                'Jurnal yang sudah diposting tidak dapat dihapus.'
            );
        }

        DB::transaction(
            function () use (
                $journalEntry
            ): void {
                $journalEntry->lines()
                    ->delete();

                $journalEntry->delete();
            }
        );

        return redirect()
            ->route(
                'journal-entries.index'
            )
            ->with(
                'success',
                'Jurnal draft berhasil dihapus.'
            );
    }
}
