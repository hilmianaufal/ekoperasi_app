<?php

namespace App\Services\Accounting;

use App\Models\AccountingAccountMapping;
use App\Models\InstallmentPayment;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\SavingTransaction;
use App\Models\ShuPayment;
use App\Models\ShuPeriod;
use DomainException;
use Illuminate\Support\Facades\DB;

class AccountingJournalService
{
    public function recordSavingTransaction(
        SavingTransaction $transaction
    ): ?JournalEntry {
        $transaction->loadMissing([
            'member:id,member_number,name',
            'savingType:id,code,name',
        ]);

        $amount = $this->money(
            $transaction->amount
        );

        if ($amount <= 0) {
            return null;
        }

        $savingMapping = match (
            strtoupper(
                (string) $transaction
                    ->savingType
                    ->code
            )
        ) {
            'POKOK' => 'principal_savings',
            'WAJIB' => 'mandatory_savings',
            'SUKARELA' => 'voluntary_savings',

            default => throw new DomainException(
                sprintf(
                    'Pemetaan jurnal untuk jenis simpanan %s belum tersedia.',
                    $transaction->savingType->code
                )
            ),
        };

        $cashAccountId = $this->accountId(
            'cash'
        );

        $savingAccountId = $this->accountId(
            $savingMapping
        );

        $isDeposit = (
            $transaction->transaction_type
            === 'deposit'
        );

        $description = sprintf(
            '%s %s anggota %s (%s).',
            $isDeposit
                ? 'Setoran'
                : 'Penarikan',
            $transaction->savingType->name,
            $transaction->member->name,
            $transaction->member->member_number
        );

        return $this->createPostedEntry(
            sourceType: 'saving_transaction',
            sourceId: $transaction->id,
            entryDate: $transaction->transaction_date,
            referenceNumber:
                $transaction->transaction_code,
            description: $description,
            userId: $transaction->user_id,
            lines: $isDeposit
                ? [
                    [
                        'accounting_account_id' =>
                            $cashAccountId,

                        'description' =>
                            $description,

                        'debit' => $amount,
                        'credit' => 0,

                        'member_id' =>
                            $transaction->member_id,
                    ],
                    [
                        'accounting_account_id' =>
                            $savingAccountId,

                        'description' =>
                            $description,

                        'debit' => 0,
                        'credit' => $amount,

                        'member_id' =>
                            $transaction->member_id,
                    ],
                ]
                : [
                    [
                        'accounting_account_id' =>
                            $savingAccountId,

                        'description' =>
                            $description,

                        'debit' => $amount,
                        'credit' => 0,

                        'member_id' =>
                            $transaction->member_id,
                    ],
                    [
                        'accounting_account_id' =>
                            $cashAccountId,

                        'description' =>
                            $description,

                        'debit' => 0,
                        'credit' => $amount,

                        'member_id' =>
                            $transaction->member_id,
                    ],
                ]
        );
    }

    public function recordLoanDisbursement(
        Loan $loan
    ): ?JournalEntry {
        if (
            !in_array(
                $loan->status,
                [
                    'active',
                    'paid',
                ],
                true
            )
        ) {
            return null;
        }

        $loan->loadMissing([
            'member:id,member_number,name',
        ]);

        /*
         * Untuk data legacy, hanya pembiayaan baru
         * selama periode import yang dianggap pencairan.
         *
         * Saldo pembiayaan sebelum periode akan dicatat
         * melalui jurnal saldo awal pada tahap backfill.
         */
        $amount = $loan->is_legacy
            ? $this->money(
                $loan->disbursed_during_import
            )
            : $this->money(
                $loan->principal_amount
            );

        if ($amount <= 0) {
            return null;
        }

        $description = sprintf(
            'Pencairan pembiayaan %s kepada %s (%s).',
            $loan->loan_number,
            $loan->member->name,
            $loan->member->member_number
        );

        return $this->createPostedEntry(
            sourceType: 'loan_disbursement',
            sourceId: $loan->id,
            entryDate:
                $loan->start_date
                ?? $loan->application_date,
            referenceNumber:
                $loan->loan_number,
            description: $description,
            userId:
                $loan->approved_by
                ?? $loan->created_by,
            lines: [
                [
                    'accounting_account_id' =>
                        $this->accountId(
                            'financing_receivable'
                        ),

                    'description' =>
                        $description,

                    'debit' => $amount,
                    'credit' => 0,

                    'member_id' =>
                        $loan->member_id,

                    'loan_id' =>
                        $loan->id,
                ],
                [
                    'accounting_account_id' =>
                        $this->accountId('cash'),

                    'description' =>
                        $description,

                    'debit' => 0,
                    'credit' => $amount,

                    'member_id' =>
                        $loan->member_id,

                    'loan_id' =>
                        $loan->id,
                ],
            ]
        );
    }

    public function recordInstallmentPayment(
        InstallmentPayment $payment
    ): ?JournalEntry {
        $payment->loadMissing([
            'installment.loan.member:id,member_number,name',
        ]);

        $installment = $payment->installment;
        $loan = $installment->loan;
        $member = $loan->member;

        $amount = $this->money(
            $payment->amount
        );

        if ($amount <= 0) {
            return null;
        }

        /*
         * Nilai bagi hasil dan administrasi diambil
         * dari pembagian pembayaran yang telah tersimpan.
         *
         * Apabila data pembagian kosong, nominal pembayaran
         * dianggap sebagai pengembalian pokok agar aplikasi
         * tidak mengarang nilai pendapatan.
         */
        $profitShare = min(
            max(
                $this->money(
                    $payment->profit_share_amount
                    ?? 0
                ),
                0
            ),
            $amount
        );

        $administration = min(
            max(
                $this->money(
                    $payment->administration_fee
                    ?? 0
                ),
                0
            ),
            max(
                $amount - $profitShare,
                0
            )
        );

        $principal = $this->money(
            $amount
            - $profitShare
            - $administration
        );

        $description = sprintf(
            'Pembayaran angsuran ke-%d pembiayaan %s dari %s (%s).',
            $installment->installment_number,
            $loan->loan_number,
            $member->name,
            $member->member_number
        );

        $lines = [
            [
                'accounting_account_id' =>
                    $this->accountId('cash'),

                'description' =>
                    $description,

                'debit' => $amount,
                'credit' => 0,

                'member_id' =>
                    $loan->member_id,

                'loan_id' =>
                    $loan->id,
            ],
        ];

        if ($principal > 0) {
            $lines[] = [
                'accounting_account_id' =>
                    $this->accountId(
                        'financing_receivable'
                    ),

                'description' =>
                    'Pengembalian pokok pembiayaan',

                'debit' => 0,
                'credit' => $principal,

                'member_id' =>
                    $loan->member_id,

                'loan_id' =>
                    $loan->id,
            ];
        }

        if ($profitShare > 0) {
            $lines[] = [
                'accounting_account_id' =>
                    $this->accountId(
                        'profit_share_revenue'
                    ),

                'description' =>
                    'Pendapatan bagi hasil',

                'debit' => 0,
                'credit' => $profitShare,

                'member_id' =>
                    $loan->member_id,

                'loan_id' =>
                    $loan->id,
            ];
        }

        if ($administration > 0) {
            $lines[] = [
                'accounting_account_id' =>
                    $this->accountId(
                        'administration_revenue'
                    ),

                'description' =>
                    'Pendapatan administrasi',

                'debit' => 0,
                'credit' => $administration,

                'member_id' =>
                    $loan->member_id,

                'loan_id' =>
                    $loan->id,
            ];
        }

        return $this->createPostedEntry(
            sourceType: 'installment_payment',
            sourceId: $payment->id,
            entryDate: $payment->payment_date,
            referenceNumber:
                $payment->payment_code,
            description: $description,
            userId: $payment->user_id,
            lines: $lines
        );
    }

    public function recordShuAllocation(
        ShuPeriod $period
    ): ?JournalEntry {
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
            return null;
        }

        $allocatedTotal = $this->money(
            $period->allocations()
                ->sum('total_shu')
        );

        if ($allocatedTotal <= 0) {
            return null;
        }

        $description = sprintf(
            'Pengakuan utang SHU anggota periode %d.',
            $period->year
        );

        return $this->createPostedEntry(
            sourceType: 'shu_allocation',
            sourceId: $period->id,
            entryDate:
                $period->calculation_date,
            referenceNumber:
                $period->code,
            description: $description,
            userId:
                $period->approved_by,
            lines: [
                [
                    'accounting_account_id' =>
                        $this->accountId(
                            'current_shu'
                        ),

                    'description' =>
                        'Pengurangan SHU tahun berjalan',

                    'debit' =>
                        $allocatedTotal,

                    'credit' => 0,
                ],
                [
                    'accounting_account_id' =>
                        $this->accountId(
                            'shu_payable'
                        ),

                    'description' =>
                        'Utang SHU kepada anggota',

                    'debit' => 0,

                    'credit' =>
                        $allocatedTotal,
                ],
            ]
        );
    }

    public function recordShuPayment(
        ShuPayment $payment
    ): ?JournalEntry {
        $payment->loadMissing([
            'allocation.member:id,member_number,name',
            'allocation.period:id,code,year',
        ]);

        $allocation = $payment->allocation;
        $member = $allocation->member;
        $period = $allocation->period;

        $amount = $this->money(
            $payment->amount
        );

        if ($amount <= 0) {
            return null;
        }

        $description = sprintf(
            'Pembayaran SHU tahun %d kepada %s (%s).',
            $period->year,
            $member->name,
            $member->member_number
        );

        return $this->createPostedEntry(
            sourceType: 'shu_payment',
            sourceId: $payment->id,
            entryDate: $payment->payment_date,
            referenceNumber:
                $payment->payment_code,
            description: $description,
            userId: $payment->user_id,
            lines: [
                [
                    'accounting_account_id' =>
                        $this->accountId(
                            'shu_payable'
                        ),

                    'description' =>
                        $description,

                    'debit' => $amount,
                    'credit' => 0,

                    'member_id' =>
                        $allocation->member_id,
                ],
                [
                    'accounting_account_id' =>
                        $this->accountId(
                            'cash'
                        ),

                    'description' =>
                        $description,

                    'debit' => 0,
                    'credit' => $amount,

                    'member_id' =>
                        $allocation->member_id,
                ],
            ]
        );
    }

    private function createPostedEntry(
        string $sourceType,
        int $sourceId,
        mixed $entryDate,
        ?string $referenceNumber,
        string $description,
        ?int $userId,
        array $lines
    ): JournalEntry {
        $normalizedLines = collect($lines)
            ->map(function (array $line): array {
                return [
                    'accounting_account_id' =>
                        (int) $line[
                            'accounting_account_id'
                        ],

                    'description' =>
                        $line['description']
                        ?? null,

                    'debit' => $this->money(
                        $line['debit']
                        ?? 0
                    ),

                    'credit' => $this->money(
                        $line['credit']
                        ?? 0
                    ),

                    'member_id' =>
                        $line['member_id']
                        ?? null,

                    'loan_id' =>
                        $line['loan_id']
                        ?? null,
                ];
            })
            ->filter(
                fn (array $line): bool =>
                    $line['debit'] > 0
                    || $line['credit'] > 0
            )
            ->values();

        if ($normalizedLines->count() < 2) {
            throw new DomainException(
                'Jurnal otomatis minimal memiliki dua baris.'
            );
        }

        $totalDebit = $this->money(
            $normalizedLines->sum('debit')
        );

        $totalCredit = $this->money(
            $normalizedLines->sum('credit')
        );

        if (
            $totalDebit <= 0
            || abs(
                $totalDebit - $totalCredit
            ) >= 0.01
        ) {
            throw new DomainException(
                sprintf(
                    'Jurnal otomatis tidak seimbang. Debit Rp%s dan kredit Rp%s.',
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
                )
            );
        }

        return DB::transaction(
            function () use (
                $sourceType,
                $sourceId,
                $entryDate,
                $referenceNumber,
                $description,
                $userId,
                $normalizedLines
            ): JournalEntry {
                $existing = JournalEntry::query()
                    ->where(
                        'source_type',
                        $sourceType
                    )
                    ->where(
                        'source_id',
                        $sourceId
                    )
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    return $existing;
                }

                $entry = JournalEntry::create([
                    'entry_date' =>
                        $entryDate,

                    'reference_number' =>
                        $referenceNumber,

                    'description' =>
                        $description,

                    'source_type' =>
                        $sourceType,

                    'source_id' =>
                        $sourceId,

                    'status' =>
                        'posted',

                    'created_by' =>
                        $userId,

                    'posted_by' =>
                        $userId,

                    'posted_at' =>
                        now(),
                ]);

                foreach (
                    $normalizedLines as $line
                ) {
                    $entry->lines()->create(
                        $line
                    );
                }

                return $entry->load(
                    'lines'
                );
            }
        );
    }

    private function accountId(
        string $mappingKey
    ): int {
        $mapping = AccountingAccountMapping::query()
            ->with('account:id,is_active,is_header')
            ->where(
                'mapping_key',
                $mappingKey
            )
            ->first();

        if (!$mapping) {
            throw new DomainException(
                "Mapping akun {$mappingKey} belum tersedia."
            );
        }

        if (
            !$mapping->account
            || !$mapping->account->is_active
            || $mapping->account->is_header
        ) {
            throw new DomainException(
                "Akun mapping {$mappingKey} tidak aktif atau bukan akun transaksi."
            );
        }

        return (int)
            $mapping->accounting_account_id;
    }

    private function money(
        mixed $value
    ): float {
        return round(
            (float) $value,
            2
        );
    }
}
