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
    /**
     * Menampilkan formulir angsuran manual
     * untuk pembiayaan hasil migrasi.
     */
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
                $outstandingPrincipal = round(
                    (float) $loan->outstanding_principal,
                    2
                );

                /*
                 * Pokok awal digunakan untuk menghitung
                 * estimasi bagi hasil keseluruhan.
                 */
                $basePrincipal = round(
                    (float) (
                        $loan->principal_amount
                        ?: $loan->opening_principal
                        ?: $outstandingPrincipal
                    ),
                    2
                );

                /*
                 * Apabila bunga data migrasi tidak tersedia,
                 * gunakan ketentuan client 1,5%.
                 */
                $profitShareRate = round(
                    (float) $loan->interest_rate,
                    2
                );

                if ($profitShareRate <= 0) {
                    $profitShareRate = 1.5;
                }

                $tenor = (int) $loan->tenor_months;

                /*
                 * Bagi hasil dihitung satu kali dari pokok,
                 * lalu dibagi rata mengikuti tenor.
                 *
                 * Jika tenor data migrasi tidak tersedia,
                 * petugas tetap dapat mengisi manual.
                 */
                $suggestedProfitShare = (
                    $tenor >= 1
                    && $tenor <= 10
                    && $basePrincipal > 0
                )
                    ? round(
                        (
                            $basePrincipal
                            * ($profitShareRate / 100)
                        ) / $tenor,
                        2
                    )
                    : 0;

                $totalProfitShare = round(
                    $basePrincipal
                    * ($profitShareRate / 100),
                    2
                );

                return [
                    'id' => (int) $loan->id,

                    'loan_number' =>
                        $loan->loan_number,

                    'member_number' =>
                        $loan->member?->member_number
                        ?? '-',

                    'member_name' =>
                        $loan->member?->name
                        ?? '-',

                    'base_principal' =>
                        $basePrincipal,

                    'outstanding_principal' =>
                        $outstandingPrincipal,

                    'profit_share_rate' =>
                        $profitShareRate,

                    'total_profit_share' =>
                        $totalProfitShare,

                    'tenor_months' =>
                        $tenor,

                    'suggested_profit_share' =>
                        $suggestedProfitShare,

                    'next_installment_number' =>
                        (
                            (int) (
                                $loan
                                    ->installments_max_installment_number
                                ?? 0
                            )
                        ) + 1,
                ];
            })
            ->values();

        $selectedLoanId = $request->integer(
            'loan_id'
        ) ?: null;

        return view(
            'installments.manual-create',
            compact(
                'loanOptions',
                'selectedLoanId'
            )
        );
    }

    /**
     * Menyimpan pembayaran angsuran manual.
     */
    public function store(
        Request $request,
        CashLedgerService $cashLedgerService,
        AccountingJournalService $journalService
    ): RedirectResponse {
        $data = $request->validate([
            'loan_id' => [
                'required',

                Rule::exists(
                    'loans',
                    'id'
                )->where(
                    function ($query): void {
                        $query
                            ->where(
                                'status',
                                'active'
                            )
                            ->where(
                                'is_legacy',
                                true
                            )
                            ->where(
                                'outstanding_principal',
                                '>',
                                0
                            );
                    }
                ),
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
            'loan_id.required' =>
                'Pembiayaan wajib dipilih.',

            'loan_id.exists' =>
                'Pembiayaan tidak ditemukan, sudah lunas, atau bukan data hasil migrasi.',

            'payment_date.required' =>
                'Tanggal pembayaran wajib diisi.',

            'payment_date.date' =>
                'Tanggal pembayaran tidak valid.',

            'payment_date.before_or_equal' =>
                'Tanggal pembayaran tidak boleh melebihi hari ini.',

            'principal_amount.required' =>
                'Angsuran pokok wajib diisi.',

            'principal_amount.numeric' =>
                'Angsuran pokok harus berupa angka.',

            'principal_amount.min' =>
                'Angsuran pokok minimal Rp1.',

            'profit_share_amount.numeric' =>
                'Bagi hasil harus berupa angka.',

            'profit_share_amount.min' =>
                'Bagi hasil tidak boleh negatif.',

            'payment_method.required' =>
                'Metode pembayaran wajib dipilih.',

            'payment_method.in' =>
                'Metode pembayaran tidak valid.',

            'reference_number.required_if' =>
                'Nomor referensi transfer wajib diisi.',

            'reference_number.max' =>
                'Nomor referensi maksimal 150 karakter.',

            'notes.max' =>
                'Catatan maksimal 1.000 karakter.',
        ]);

        $payment = DB::transaction(
            function () use (
                $data,
                $cashLedgerService,
                $journalService
            ): InstallmentPayment {
                /*
                 * Kunci data pembiayaan agar dua pembayaran
                 * tidak mengurangi saldo secara bersamaan.
                 */
                $loan = Loan::query()
                    ->with([
                        'member:id,member_number,name',
                    ])
                    ->lockForUpdate()
                    ->findOrFail(
                        $data['loan_id']
                    );

                if (
                    !(bool) $loan->is_legacy
                    || $loan->status !== 'active'
                ) {
                    throw ValidationException::withMessages([
                        'loan_id' =>
                            'Pembiayaan ini tidak dapat menerima angsuran manual.',
                    ]);
                }

                $currentOutstanding = round(
                    (float) $loan->outstanding_principal,
                    2
                );

                if ($currentOutstanding <= 0) {
                    throw ValidationException::withMessages([
                        'loan_id' =>
                            'Pembiayaan ini sudah tidak memiliki sisa pokok.',
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

                if ($principal > $currentOutstanding) {
                    throw ValidationException::withMessages([
                        'principal_amount' =>
                            'Angsuran pokok melebihi sisa pembiayaan Rp'
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
                 * Angsuran hanya terdiri dari:
                 * pokok + bagi hasil.
                 *
                 * Tidak ada biaya administrasi.
                 */
                $paymentAmount = round(
                    $principal
                    + $profitShare,
                    2
                );

                if ($paymentAmount <= 0) {
                    throw ValidationException::withMessages([
                        'principal_amount' =>
                            'Total pembayaran harus lebih dari Rp0.',
                    ]);
                }

                $nextNumber = (
                    (int) LoanInstallment::query()
                        ->where(
                            'loan_id',
                            $loan->id
                        )
                        ->max(
                            'installment_number'
                        )
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
                    $installmentNotes .=
                        ' ' . $notes;
                }

                $paymentDate = Carbon::parse(
                    $data['payment_date']
                )->startOfDay();

                /*
                 * Buat satu baris angsuran yang langsung
                 * berstatus lunas karena pembayaran dicatat
                 * pada saat yang sama.
                 */
                $installment = LoanInstallment::create([
                    'loan_id' =>
                        $loan->id,

                    'installment_number' =>
                        $nextNumber,

                    'due_date' =>
                        $paymentDate->toDateString(),

                    'principal_amount' =>
                        $principal,

                    'interest_amount' =>
                        $profitShare,

                    'total_amount' =>
                        $paymentAmount,

                    'paid_amount' =>
                        $paymentAmount,

                    'paid_at' =>
                        $paymentDate,

                    'status' =>
                        'paid',

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
                     * Pada pembiayaan legacy,
                     * remaining_after menunjukkan
                     * sisa pokok pembiayaan.
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

                    /*
                     * Administrasi tidak boleh dicatat
                     * pada pembayaran angsuran.
                     */
                    'administration_fee' =>
                        0,
                ]);

                $payment->update([
                    'payment_code' => sprintf(
                        'ANG-%s-%06d',
                        $paymentDate->format(
                            'Ymd'
                        ),
                        $payment->id
                    ),
                ]);

                /*
                 * Kurangi hanya sisa pokok pembiayaan.
                 * Bagi hasil tidak mengurangi pokok.
                 */
                $loan->update([
                    'outstanding_principal' =>
                        $remainingPrincipal,

                    'profit_share_paid' =>
                        round(
                            (float) $loan->profit_share_paid
                            + $profitShare,
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
                 * Catat kas masuk dan jurnal otomatis.
                 * Service menggunakan source_type dan
                 * source_id untuk mencegah duplikasi.
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
                'Angsuran berhasil dicatat. Sisa pokok, kas, dan jurnal telah diperbarui.'
            );
    }
}
