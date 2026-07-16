<?php

namespace App\Http\Controllers;

use App\Models\AppSetting;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Models\Member;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoanController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('search'));
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $validStatuses = [
            'pending',
            'active',
            'rejected',
            'paid',
            'cancelled',
        ];

        $loans = Loan::query()
            ->with([
                'member:id,member_number,name,photo',
                'creator:id,name',
                'approver:id,name',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('loan_number', 'like', "%{$search}%")
                        ->orWhereHas('member', function ($memberQuery) use ($search) {
                            $memberQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('member_number', 'like', "%{$search}%");
                        });
                });
            })
            ->when(
                in_array($status, $validStatuses, true),
                fn ($query) => $query->where('status', $status)
            )
            ->when(
                $dateFrom,
                fn ($query) => $query->whereDate(
                    'application_date',
                    '>=',
                    $dateFrom
                )
            )
            ->when(
                $dateTo,
                fn ($query) => $query->whereDate(
                    'application_date',
                    '<=',
                    $dateTo
                )
            )
            ->latest('application_date')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $activeLoanTotal = Loan::query()
            ->where('status', 'active')
            ->where(function ($query) {
                $query
                    ->where('is_legacy', false)
                    ->orWhereNull('is_legacy');
            })
            ->sum('total_amount');

        $activePaidTotal = LoanInstallment::query()
            ->whereHas('loan', function ($query) {
                $query
                    ->where('status', 'active')
                    ->where(function ($loanQuery) {
                        $loanQuery
                            ->where('is_legacy', false)
                            ->orWhereNull('is_legacy');
                    });
            })
            ->sum('paid_amount');

        $legacyOutstanding = Loan::query()
            ->where('status', 'active')
            ->where('is_legacy', true)
            ->sum('outstanding_principal');

        $statistics = [
            'pending' => Loan::query()
                ->where('status', 'pending')
                ->count(),

            'active' => Loan::query()
                ->where('status', 'active')
                ->count(),

            'paid' => Loan::query()
                ->where('status', 'paid')
                ->count(),

            'outstanding' => round(
                max(
                    (float) $activeLoanTotal - (float) $activePaidTotal,
                    0
                ) + (float) $legacyOutstanding,
                2
            ),
        ];

        return view('loans.index', compact(
            'loans',
            'statistics',
            'search',
            'status',
            'dateFrom',
            'dateTo'
        ));
    }

    public function create(Request $request): View
    {
        $members = Member::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get([
                'id',
                'member_number',
                'name',
            ]);

        $selectedMemberId = $request->integer('member_id') ?: null;
        $setting = AppSetting::current();

        return view('loans.create', compact(
            'members',
            'selectedMemberId',
            'setting'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $setting = AppSetting::current();

        $principalRules = [
            'required',
            'numeric',
            'min:' . (float) $setting->minimum_loan_amount,
        ];

        if ($setting->maximum_loan_amount !== null) {
            $principalRules[] = 'max:'
                . (float) $setting->maximum_loan_amount;
        }

        $data = $request->validate([
            'member_id' => [
                'required',
                Rule::exists('members', 'id')->where(
                    fn ($query) => $query->where('status', 'active')
                ),
            ],

            'application_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'principal_amount' => $principalRules,

            'interest_rate' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],

            'tenor_months' => [
                'required',
                'integer',
                'min:1',
                'max:10',
            ],

            'purpose' => [
                'required',
                'string',
                'max:2000',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ], [
            'member_id.required' => 'Anggota wajib dipilih.',
            'member_id.exists' => 'Anggota tidak ditemukan atau sedang tidak aktif.',

            'application_date.required' => 'Tanggal pengajuan wajib diisi.',
            'application_date.date' => 'Tanggal pengajuan tidak valid.',
            'application_date.before_or_equal' => 'Tanggal pengajuan tidak boleh melebihi hari ini.',

            'principal_amount.required' => 'Nominal pinjaman wajib diisi.',
            'principal_amount.numeric' => 'Nominal pinjaman harus berupa angka.',
            'principal_amount.min' => 'Nominal pinjaman minimal Rp'
                . number_format(
                    (float) $setting->minimum_loan_amount,
                    0,
                    ',',
                    '.'
                )
                . '.',

            'principal_amount.max' => $setting->maximum_loan_amount
                ? 'Nominal pinjaman maksimal Rp'
                    . number_format(
                        (float) $setting->maximum_loan_amount,
                        0,
                        ',',
                        '.'
                    )
                    . '.'
                : 'Nominal pinjaman melebihi batas maksimal.',

            'interest_rate.required' => 'Persentase bunga wajib diisi.',
            'interest_rate.numeric' => 'Persentase bunga harus berupa angka.',
            'interest_rate.min' => 'Persentase bunga minimal 0%.',
            'interest_rate.max' => 'Persentase bunga maksimal 100%.',

            'tenor_months.required' => 'Tenor pinjaman wajib diisi.',
            'tenor_months.integer' => 'Tenor pinjaman harus berupa angka bulat.',
            'tenor_months.min' => 'Tenor pinjaman minimal 1 bulan.',
            'tenor_months.max' => 'Tenor pinjaman maksimal 10 bulan.',

            'purpose.required' => 'Tujuan pinjaman wajib diisi.',
            'purpose.max' => 'Tujuan pinjaman maksimal 2.000 karakter.',
            'notes.max' => 'Catatan maksimal 2.000 karakter.',
        ]);

        $principal = round((float) $data['principal_amount'], 2);
        $interestRate = round((float) $data['interest_rate'], 2);
        $tenor = (int) $data['tenor_months'];

        $calculation = $this->calculateLoan(
            $principal,
            $interestRate,
            $tenor
        );

        $loan = DB::transaction(function () use (
            $data,
            $principal,
            $interestRate,
            $tenor,
            $calculation
        ): Loan {
            $loan = Loan::create([
                'member_id' => $data['member_id'],
                'created_by' => auth()->id(),
                'application_date' => $data['application_date'],
                'principal_amount' => $principal,
                'interest_rate' => $interestRate,
                'tenor_months' => $tenor,
                'interest_amount' => $calculation['interest_amount'],
                'total_amount' => $calculation['total_amount'],
                'monthly_installment' => $calculation['monthly_installment'],
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'is_legacy' => false,
            ]);

            $loan->update([
                'loan_number' => sprintf(
                    'PIN-%s-%06d',
                    now()->format('Y'),
                    $loan->id
                ),
            ]);

            return $loan;
        });

        return redirect()
            ->route('loans.show', $loan)
            ->with(
                'success',
                'Pengajuan pinjaman berhasil dibuat dan menunggu persetujuan.'
            );
    }

    public function show(Loan $loan): View
    {
        $loan->load([
            'member',
            'creator:id,name',
            'approver:id,name',
            'installments.payments',
        ]);

        $paidAmount = round(
            (float) $loan->installments->sum('paid_amount'),
            2
        );

        $summary = [
            'paid_amount' => $paidAmount,

            'remaining_amount' => $loan->is_legacy
                ? max((float) $loan->outstanding_principal, 0)
                : max(
                    round((float) $loan->total_amount - $paidAmount, 2),
                    0
                ),

            'paid_installments' => $loan->installments
                ->where('status', 'paid')
                ->count(),
        ];

        return view('loans.show', compact(
            'loan',
            'summary'
        ));
    }

    public function approve(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $data = $request->validate([
            'start_date' => [
                'required',
                'date',
            ],
        ], [
            'start_date.required' => 'Tanggal pencairan wajib diisi.',
            'start_date.date' => 'Tanggal pencairan tidak valid.',
        ]);

        DB::transaction(function () use ($loan, $data): void {
            $lockedLoan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loan->id);

            if ($lockedLoan->status !== 'pending') {
                throw ValidationException::withMessages([
                    'start_date' => 'Pinjaman ini sudah diproses sebelumnya.',
                ]);
            }

            if ((bool) $lockedLoan->is_legacy) {
                throw ValidationException::withMessages([
                    'start_date' => 'Pembiayaan hasil migrasi tidak dapat disetujui sebagai pinjaman baru.',
                ]);
            }

            $startDate = Carbon::parse($data['start_date'])
                ->startOfDay();

            if (
                $lockedLoan->application_date
                && $startDate->lt($lockedLoan->application_date)
            ) {
                throw ValidationException::withMessages([
                    'start_date' => 'Tanggal pencairan tidak boleh lebih awal dari tanggal pengajuan.',
                ]);
            }

            $tenor = (int) $lockedLoan->tenor_months;

            if ($tenor < 1 || $tenor > 10) {
                throw ValidationException::withMessages([
                    'start_date' => 'Tenor pinjaman harus antara 1 sampai 10 bulan.',
                ]);
            }

            $principal = round(
                (float) $lockedLoan->principal_amount,
                2
            );

            $interestRate = round(
                (float) $lockedLoan->interest_rate,
                2
            );

            if ($principal <= 0) {
                throw ValidationException::withMessages([
                    'start_date' => 'Nominal pokok pinjaman harus lebih dari Rp0.',
                ]);
            }

            /*
             * Bunga dihitung satu kali dari seluruh pokok.
             * Contoh: Rp1.000.000 x 1,5% = Rp15.000.
             */
            $totalInterest = round(
                $principal * ($interestRate / 100),
                2
            );

            $totalLoan = round(
                $principal + $totalInterest,
                2
            );

            /*
             * Pokok dan bunga total dibagi rata mengikuti tenor.
             */
            $principalPerMonth = round(
                $principal / $tenor,
                2
            );

            $interestPerMonth = round(
                $totalInterest / $tenor,
                2
            );

            $allocatedPrincipal = 0;
            $allocatedInterest = 0;
            $maturityDate = null;

            $lockedLoan->installments()->delete();

            for ($number = 1; $number <= $tenor; $number++) {
                $principalPart = $number === $tenor
                    ? round(
                        $principal - $allocatedPrincipal,
                        2
                    )
                    : $principalPerMonth;

                $interestPart = $number === $tenor
                    ? round(
                        $totalInterest - $allocatedInterest,
                        2
                    )
                    : $interestPerMonth;

                $installmentTotal = round(
                    $principalPart + $interestPart,
                    2
                );

                $allocatedPrincipal = round(
                    $allocatedPrincipal + $principalPart,
                    2
                );

                $allocatedInterest = round(
                    $allocatedInterest + $interestPart,
                    2
                );

                $dueDate = $startDate
                    ->copy()
                    ->addMonthsNoOverflow($number);

                $lockedLoan->installments()->create([
                    'installment_number' => $number,
                    'due_date' => $dueDate,
                    'principal_amount' => $principalPart,
                    'interest_amount' => $interestPart,
                    'total_amount' => $installmentTotal,
                    'paid_amount' => 0,
                    'paid_at' => null,
                    'status' => 'unpaid',
                    'notes' => null,
                ]);

                $maturityDate = $dueDate;
            }

            $monthlyInstallment = round(
                $totalLoan / $tenor,
                2
            );

            $lockedLoan->update([
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'start_date' => $startDate,
                'maturity_date' => $maturityDate,
                'interest_amount' => $totalInterest,
                'total_amount' => $totalLoan,
                'monthly_installment' => $monthlyInstallment,
                'status' => 'active',
                'rejection_reason' => null,
            ]);
        });

        return redirect()
            ->route('loans.show', $loan)
            ->with(
                'success',
                'Pinjaman berhasil disetujui. Bunga keseluruhan telah dibagi rata mengikuti tenor.'
            );
    }

    public function reject(
        Request $request,
        Loan $loan
    ): RedirectResponse {
        $data = $request->validate([
            'rejection_reason' => [
                'required',
                'string',
                'max:2000',
            ],
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi.',
            'rejection_reason.max' => 'Alasan penolakan maksimal 2.000 karakter.',
        ]);

        DB::transaction(function () use ($loan, $data): void {
            $lockedLoan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loan->id);

            if ($lockedLoan->status !== 'pending') {
                throw ValidationException::withMessages([
                    'rejection_reason' => 'Pinjaman ini sudah diproses sebelumnya.',
                ]);
            }

            $lockedLoan->update([
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'status' => 'rejected',
                'rejection_reason' => $data['rejection_reason'],
            ]);
        });

        return redirect()
            ->route('loans.show', $loan)
            ->with(
                'success',
                'Pengajuan pinjaman telah ditolak.'
            );
    }

    public function cancel(Loan $loan): RedirectResponse
    {
        DB::transaction(function () use ($loan): void {
            $lockedLoan = Loan::query()
                ->lockForUpdate()
                ->findOrFail($loan->id);

            if ($lockedLoan->status !== 'pending') {
                throw ValidationException::withMessages([
                    'loan' => 'Hanya pengajuan yang masih menunggu yang dapat dibatalkan.',
                ]);
            }

            $lockedLoan->update([
                'status' => 'cancelled',
            ]);
        });

        return redirect()
            ->route('loans.index')
            ->with(
                'success',
                'Pengajuan pinjaman berhasil dibatalkan.'
            );
    }

    private function calculateLoan(
        float $principal,
        float $interestRate,
        int $tenor
    ): array {
        if ($principal <= 0) {
            throw new \InvalidArgumentException(
                'Pokok pinjaman harus lebih dari Rp0.'
            );
        }

        if ($tenor < 1 || $tenor > 10) {
            throw new \InvalidArgumentException(
                'Tenor pinjaman harus antara 1 sampai 10 bulan.'
            );
        }

        $interestAmount = round(
            $principal * ($interestRate / 100),
            2
        );

        $totalAmount = round(
            $principal + $interestAmount,
            2
        );

        $monthlyInstallment = round(
            $totalAmount / $tenor,
            2
        );

        return [
            'interest_amount' => $interestAmount,
            'total_amount' => $totalAmount,
            'monthly_installment' => $monthlyInstallment,
        ];
    }
}
