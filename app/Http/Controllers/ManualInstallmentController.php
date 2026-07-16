<?php

namespace App\Http\Controllers;

use App\Models\InstallmentPayment;
use App\Models\Loan;
use App\Models\LoanInstallment;
use App\Services\Accounting\AccountingJournalService;
use App\Services\CashLedgerService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ManualInstallmentController extends Controller
{
    public function create(Request $request): View
    {
        $loans = Loan::query()
            ->with([
                'member:id,member_number,name',
            ])
            ->withMax(
                'installments',
                'installment_number'
            )
            ->where('status', 'active')
            ->where('is_legacy', true)
            ->where('outstanding_principal', '>', 0)
            ->orderBy('loan_number')
            ->get();

        $loanOptions = $loans
            ->map(function (Loan $loan): array {
                $outstanding = round(
                    (float) $loan->outstanding_principal,
                    2
                );

                return [
                    'id' => (int) $loan->id,

                    'loan_number' => $loan->loan_number,

                    'member_number' =>
                        $loan->member?->member_number
                        ?? '-',

                    'member_name' =>
                        $loan->member?->name
                        ?? '-',

                    'outstanding_principal' =>
                        $outstanding,

                    /*
                     * Hanya estimasi tombol bantuan.
                     * Nominal tetap dapat diubah petugas.
                     */
                    'suggested_profit_share' => round(
                        $outstanding * 0.015,
                        2
                    ),

                    'next_installment_number' =>
                        ((int) (
                            $loan
                                ->installments_max_installment_number
                            ?? 0
                        )) + 1,
                ];
            })
            ->values();

        $selectedLoanId = $request->integer('loan_id')
            ?: null;

        return view(
            'installments.manual-create',
            compact(
                'loanOptions',
                'selectedLoanId'
            )
        );
    }

    public function store(
        Request $request,
        CashLedgerService $cashLedgerService,
        AccountingJournalService $journalService
    ): RedirectResponse {
        $data = $request->validate([
            'loan_id' => [
                'required',

                Rule::exists('loans', 'id')
                    ->where(function ($query): void {
                        $query
                            ->where('status', 'active')
                            ->where('is_legacy', true)
                            ->where(
                                'outstanding_principal',
                                '>',
                                0
                            );
                    }),
            ],

            'payment_date' => [
                'required',
                'date',
                'before_or_equal:today',
            ],

            'principal_amount' => [
                'required',
                'numeric',
                'min:1',
            ],

            'profit_share_amount' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'administration_fee' => [
                'nullable',
                'numeric',
                'min:0',
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
                'required_if:payment_method,transfer',
                'string',
                'max:150',
            ],

            'notes' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'loan_id.required'
                => 'Pembiayaan wajib dipilih.',

            'loan_id.exists'
                => 'Pembiayaan tidak ditemukan, sudah lunas, atau bukan data hasil import.',

            'payment_date.required'
                => 'Tanggal pembayaran wajib diisi.',

            'payment_date.before_or_equal'
                => 'Tanggal pembayaran tidak boleh melebihi hari ini.',

            'principal_amount.required'
                => 'Angsuran pokok wajib diisi.',

            'principal_amount.numeric'
                => 'Angsuran pokok harus berupa angka.',

            'principal_amount.min'
                => 'Angsuran pokok minimal Rp1.',

            'profit_share_amount.numeric'
                => 'Bagi hasil harus berupa angka.',

            'profit_share_amount.min'
                => 'Bagi hasil tidak boleh negatif.',

            'administration_fee.numeric'
                => 'Biaya administrasi harus berupa angka.',

            'administration_fee.min'
                => 'Biaya administrasi tidak boleh negatif.',

            'payment_method.required'
                => 'Metode pembayaran wajib dipilih.',

            'reference_number.required_if'
                => 'Nomor referensi transfer wajib diisi.',
        ]);

        $payment = DB::transaction(
            function () use (
                $data,
                $cashLedgerService,
                $journalService
            ): InstallmentPayment {
                $loan = Loan::query()
                    ->with([
                        'member:id,member_number,name',
                    ])
                    ->lockForUpdate()
                    ->findOrFail($data['loan_id']);

                if (
                    !$loan->is_legacy
                    || $loan->status !== 'active'
                ) {
                    throw ValidationException::withMessages([
                        'loan_id'
                            => 'Pembiayaan ini tidak dapat menerima angsuran manual.',
                    ]);
                }

                $currentOutstanding = round(
                    (float) $loan->outstanding_principal,
                    2
                );

                if ($currentOutstanding <= 0) {
                    throw ValidationException::withMessages([
                        'loan_id'
                            => 'Pembiayaan ini sudah tidak memiliki sisa pokok.',
                    ]);
                }

                $principal = round(
                    (float) $data['principal_amount'],
                    2
                );

                $profitShare = round(
                    (float) (
                        $data['profit_share_amount']
                        ?? 0
                    ),
                    2
                );

                $administration = round(
                    (float) (
                        $data['administration_fee']
                        ?? 0
                    ),
                    2
                );

                if ($principal > $currentOutstanding) {
                    throw ValidationException::withMessages([
                        'principal_amount'
                            => 'Angsuran pokok melebihi sisa pembiayaan sebesar Rp'
                            . number_format(
                                $currentOutstanding,
                                0,
                                ',',
                                '.'
                            )
                            . '.',
                    ]);
                }

                $remainingPrincipal = max(
                    round(
                        $currentOutstanding
                        - $principal,
                        2
                    ),
                    0
                );

                /*
                 * Administrasi disimpan pada pembayaran,
                 * bukan sebagai bagian pokok pembiayaan.
                 */
                $installmentAmount = round(
                    $principal + $profitShare,
                    2
                );

                $paymentAmount = round(
                    $installmentAmount
                    + $administration,
                    2
                );

                $nextNumber = (
                    (int) LoanInstallment::query()
                        ->where('loan_id', $loan->id)
                        ->max('installment_number')
                ) + 1;

                $notes = trim(
                    (string) (
                        $data['notes']
                        ?? ''
                    )
                );

                $installmentNotes =
                    'Angsuran manual setelah migrasi data.';

                if ($notes !== '') {
                    $installmentNotes .= ' ' . $notes;
                }

                $paymentDate = Carbon::parse(
                    $data['payment_date']
                );

                $installment = LoanInstallment::create([
                    'loan_id' => $loan->id,

                    'installment_number' =>
                        $nextNumber,

                    'due_date' =>
                        $paymentDate->toDateString(),

                    'principal_amount' =>
                        $principal,

                    'interest_amount' =>
                        $profitShare,

                    'total_amount' =>
                        $installmentAmount,

                    'paid_amount' =>
                        $installmentAmount,

                    'paid_at' =>
                        $paymentDate,

                    'status' => 'paid',

                    'notes' =>
                        $installmentNotes,

                    'reported_remaining_principal' =>
                        $remainingPrincipal,
                ]);

                $payment = InstallmentPayment::create([
                    'loan_installment_id' =>
                        $installment->id,

                    'user_id' =>
                        auth()->id(),

                    'payment_date' =>
                        $paymentDate->toDateString(),

                    'amount' =>
                        $paymentAmount,

                    /*
                     * Untuk pembayaran data legacy,
                     * kolom ini menunjukkan sisa pokok
                     * pembiayaan setelah pembayaran.
                     */
                    'remaining_after' =>
                        $remainingPrincipal,

                    'payment_method' =>
                        $data['payment_method'],

                    'reference_number' =>
                        $data['reference_number']
                        ?? null,

                    'notes' =>
                        $notes !== ''
                            ? $notes
                            : null,

                    'principal_amount' =>
                        $principal,

                    'profit_share_amount' =>
                        $profitShare,

                    'administration_fee' =>
                        $administration,
                ]);

                $payment->update([
                    'payment_code' => sprintf(
                        'ANG-%s-%06d',
                        $paymentDate->format('Ymd'),
                        $payment->id
                    ),
                ]);

                $loan->update([
                    'outstanding_principal' =>
                        $remainingPrincipal,

                    'profit_share_paid' => round(
                        (float) $loan->profit_share_paid
                        + $profitShare,
                        2
                    ),

                    'administration_paid' => round(
                        (float) $loan->administration_paid
                        + $administration,
                        2
                    ),

                    'status' =>
                        $remainingPrincipal <= 0
                            ? 'paid'
                            : 'active',
                ]);

                $payment->load([
                    'installment.loan.member',
                ]);

                /*
                 * Kedua service menggunakan source_type
                 * dan source_id sehingga aman dari duplikasi.
                 */
                $cashLedgerService
                    ->recordInstallmentPayment(
                        $payment
                    );

                $journalService
                    ->recordInstallmentPayment(
                        $payment
                    );

                return $payment;
            }
        );

        return redirect()
            ->route(
                'installment-payments.show',
                $payment
            )
            ->with(
                'success',
                'Angsuran manual berhasil dicatat. Saldo pembiayaan, kas, dan jurnal telah diperbarui.'
            );
    }
}
