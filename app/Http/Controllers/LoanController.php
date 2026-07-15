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
                                ->orWhere(
                                    'member_number',
                                    'like',
                                    "%{$search}%"
                                );
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
            ->sum('total_amount');

        $activePaidTotal = LoanInstallment::query()
            ->whereHas('loan', function ($query) {
                $query->where('status', 'active');
            })
            ->sum('paid_amount');

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

            'outstanding' => max(
                (float) $activeLoanTotal - (float) $activePaidTotal,
                0
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
                'max:120',
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
            'tenor_months.max' => 'Tenor pinjaman maksimal 120 bulan.',

            'purpose.required' => 'Tujuan pinjaman wajib diisi.',
            'purpose.max' => 'Tujuan pinjaman maksimal 2.000 karakter.',
            'notes.max' => 'Catatan maksimal 2.000 karakter.',
        ]);

        $calculation = $this->calculateLoan(
            (float) $data['principal_amount'],
            (float) $data['interest_rate'],
            (int) $data['tenor_months']
        );

        $loan = DB::transaction(function () use (
            $data,
            $calculation
        ): Loan {
            $loan = Loan::create([
                'member_id' => $data['member_id'],
                'created_by' => auth()->id(),
                'application_date' => $data['application_date'],
                'principal_amount' => $data['principal_amount'],
                'interest_rate' => $data['interest_rate'],
                'tenor_months' => $data['tenor_months'],
                'interest_amount' => $calculation['interest_amount'],
                'total_amount' => $calculation['total_amount'],
                'monthly_installment' => $calculation['monthly_installment'],
                'purpose' => $data['purpose'],
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
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

        $paidAmount = (float) $loan->installments
            ->sum('paid_amount');

        $summary = [
            'paid_amount' => $paidAmount,

            'remaining_amount' => max(
                (float) $loan->total_amount - $paidAmount,
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

            $startDate = Carbon::parse($data['start_date'])
                ->startOfDay();

            if ($startDate->lt($lockedLoan->application_date)) {
                throw ValidationException::withMessages([
                    'start_date' => 'Tanggal pencairan tidak boleh lebih awal dari tanggal pengajuan.',
                ]);
            }

            $lockedLoan->installments()->delete();

            $tenor = (int) $lockedLoan->tenor_months;
            $principal = (float) $lockedLoan->principal_amount;
            $interestRate = (float) $lockedLoan->interest_rate;

            $principalPerMonth = round(
                $principal / $tenor,
                2
            );

            $interestPerMonth = round(
                $principal * ($interestRate / 100),
                2
            );

            $allocatedPrincipal = 0;
            $totalInterest = 0;
            $totalLoan = 0;
            $maturityDate = null;

            for ($number = 1; $number <= $tenor; $number++) {
                $principalPart = $number === $tenor
                    ? round(
                        $principal - $allocatedPrincipal,
                        2
                    )
                    : $principalPerMonth;

                $allocatedPrincipal = round(
                    $allocatedPrincipal + $principalPart,
                    2
                );

                $installmentTotal = round(
                    $principalPart + $interestPerMonth,
                    2
                );

                $dueDate = $startDate
                    ->copy()
                    ->addMonthsNoOverflow($number);

                $lockedLoan->installments()->create([
                    'installment_number' => $number,
                    'due_date' => $dueDate,
                    'principal_amount' => $principalPart,
                    'interest_amount' => $interestPerMonth,
                    'total_amount' => $installmentTotal,
                    'paid_amount' => 0,
                    'paid_at' => null,
                    'status' => 'unpaid',
                    'notes' => null,
                ]);

                $totalInterest = round(
                    $totalInterest + $interestPerMonth,
                    2
                );

                $totalLoan = round(
                    $totalLoan + $installmentTotal,
                    2
                );

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
                'Pinjaman berhasil disetujui dan jadwal angsuran telah dibuat.'
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
        $monthlyInterest = round(
            $principal * ($interestRate / 100),
            2
        );

        $interestAmount = round(
            $monthlyInterest * $tenor,
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
