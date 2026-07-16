<?php

namespace App\Services\Imports;

use App\Models\ImportBatch;
use App\Models\ImportMemberMapping;
use App\Models\ImportRow;
use App\Models\Member;
use App\Models\SavingTransaction;
use App\Models\SavingType;
use Carbon\Carbon;
use DomainException;
use Illuminate\Support\Facades\DB;

class MembersSavingsImportService
{
    /**
     * Memasukkan anggota dan transaksi simpanan
     * dari batch preview ke tabel utama aplikasi.
     *
     * @return array{
     *     member_count:int,
     *     new_member_count:int,
     *     transaction_count:int
     * }
     */
    public function import(
        ImportBatch $batch,
        ?int $userId
    ): array {
        return DB::transaction(function () use (
            $batch,
            $userId
        ): array {
            $batch = ImportBatch::query()
                ->lockForUpdate()
                ->findOrFail($batch->id);

            $this->validateBatch($batch);

            $batch->update([
                'status' => 'processing',
                'error_message' => null,
            ]);

            $savingTypes = $this->resolveSavingTypes();

            $mappings = $batch->mappings()
                ->whereIn('status', [
                    'new',
                    'matched',
                ])
                ->orderBy('source_number')
                ->get();

            $memberCount = 0;
            $newMemberCount = 0;
            $transactionCount = 0;

            foreach ($mappings as $mapping) {
                [$member, $wasCreated] = $this->resolveMember(
                    $mapping
                );

                $this->ensureMemberCanBeImported(
                    $member,
                    $batch
                );

                $mapping->update([
                    'member_id' => $member->id,
                ]);

                $rows = $batch->rows()
                    ->where(
                        'source_number',
                        $mapping->source_number
                    )
                    ->orderBy('period_date')
                    ->orderBy('row_number')
                    ->get();

                if ($rows->isEmpty()) {
                    throw new DomainException(
                        "Data transaksi untuk {$mapping->canonical_name} tidak ditemukan."
                    );
                }

                $transactionCount += $this->importMemberSavings(
                    batch: $batch,
                    mapping: $mapping,
                    member: $member,
                    rows: $rows,
                    savingTypes: $savingTypes,
                    userId: $userId
                );

                $mapping->update([
                    'status' => 'imported',
                ]);

                $memberCount++;

                if ($wasCreated) {
                    $newMemberCount++;
                }
            }

            $batch->rows()
                ->where('status', 'ready')
                ->update([
                    'status' => 'imported',
                ]);

            /*
             * Status tetap previewed karena data pembiayaan
             * dan angsuran belum diproses pada tahap ini.
             */
            $batch->update([
                'status' => 'previewed',
                'imported_member_count' => $memberCount,
                'imported_saving_count' => $transactionCount,
                'members_savings_imported_at' => now(),
                'error_message' => null,
            ]);

            return [
                'member_count' => $memberCount,
                'new_member_count' => $newMemberCount,
                'transaction_count' => $transactionCount,
            ];
        });
    }

    /**
     * Memastikan batch aman dan siap diproses.
     */
    private function validateBatch(
        ImportBatch $batch
    ): void {
        if ($batch->members_savings_imported_at) {
            throw new DomainException(
                'Anggota dan simpanan dari batch ini sudah pernah diimpor.'
            );
        }

        if ($batch->status !== 'previewed') {
            throw new DomainException(
                'Batch belum siap diproses. Lakukan preview terlebih dahulu.'
            );
        }

        $existingTransaction = SavingTransaction::query()
            ->where('import_batch_id', $batch->id)
            ->exists();

        if ($existingTransaction) {
            throw new DomainException(
                'Ditemukan transaksi lama dari batch ini. Import dihentikan untuk mencegah data ganda.'
            );
        }

        $reviewCount = $batch->mappings()
            ->where('status', 'review')
            ->count();

        if ($reviewCount > 0) {
            throw new DomainException(
                "Masih terdapat {$reviewCount} nama anggota yang perlu diperiksa."
            );
        }

        $invalidMatchedCount = $batch->mappings()
            ->where('status', 'matched')
            ->whereNull('member_id')
            ->count();

        if ($invalidMatchedCount > 0) {
            throw new DomainException(
                'Terdapat anggota berstatus cocok tetapi belum terhubung dengan data anggota aplikasi.'
            );
        }

        $processableCount = $batch->mappings()
            ->whereIn('status', [
                'new',
                'matched',
            ])
            ->count();

        if ($processableCount === 0) {
            throw new DomainException(
                'Tidak ada anggota yang dapat diproses.'
            );
        }
    }

    /**
     * Menyiapkan tiga jenis simpanan utama.
     *
     * @return array{
     *     principal:SavingType,
     *     mandatory:SavingType,
     *     voluntary:SavingType
     * }
     */
    private function resolveSavingTypes(): array
    {
        $principal = SavingType::firstOrCreate(
            [
                'code' => 'POKOK',
            ],
            [
                'name' => 'Simpanan Pokok',
                'description' => 'Simpanan pokok anggota koperasi.',
                'default_amount' => 0,
                'is_withdrawable' => false,
                'is_active' => true,
            ]
        );

        $mandatory = SavingType::firstOrCreate(
            [
                'code' => 'WAJIB',
            ],
            [
                'name' => 'Simpanan Wajib',
                'description' => 'Simpanan wajib anggota koperasi.',
                'default_amount' => 0,
                'is_withdrawable' => false,
                'is_active' => true,
            ]
        );

        $voluntary = SavingType::firstOrCreate(
            [
                'code' => 'SUKARELA',
            ],
            [
                'name' => 'Simpanan Sukarela',
                'description' => 'Tabungan sukarela anggota koperasi.',
                'default_amount' => 0,
                'is_withdrawable' => true,
                'is_active' => true,
            ]
        );

        return [
            'principal' => $principal,
            'mandatory' => $mandatory,
            'voluntary' => $voluntary,
        ];
    }

    /**
     * Mengambil anggota yang sudah cocok atau membuat anggota baru.
     *
     * @return array{0:Member,1:bool}
     */
    private function resolveMember(
        ImportMemberMapping $mapping
    ): array {
        if ($mapping->member_id) {
            return [
                Member::query()->findOrFail(
                    $mapping->member_id
                ),
                false,
            ];
        }

        $sameNameMembers = Member::query()
            ->where(
                'name',
                $mapping->canonical_name
            )
            ->get();

        if ($sameNameMembers->count() > 1) {
            throw new DomainException(
                "Nama {$mapping->canonical_name} ditemukan lebih dari satu kali di data anggota."
            );
        }

        if ($sameNameMembers->count() === 1) {
            return [
                $sameNameMembers->first(),
                false,
            ];
        }

        $member = Member::create([
            'member_number' => $this->generateMemberNumber(
                $mapping->source_number
            ),
            'name' => $mapping->canonical_name,
            'gender' => null,
            'place_of_birth' => null,
            'date_of_birth' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'join_date' => null,
            'status' => 'active',
            'photo' => null,
        ]);

        return [
            $member,
            true,
        ];
    }

    /**
     * Membuat nomor anggota berdasarkan nomor dari Excel.
     */
    private function generateMemberNumber(
        int $sourceNumber
    ): string {
        $baseNumber = 'AGT-' . str_pad(
            (string) $sourceNumber,
            4,
            '0',
            STR_PAD_LEFT
        );

        $memberNumber = $baseNumber;
        $suffix = 1;

        while (
            Member::query()
                ->where(
                    'member_number',
                    $memberNumber
                )
                ->exists()
        ) {
            $suffix++;

            $memberNumber = sprintf(
                '%s-%d',
                $baseNumber,
                $suffix
            );
        }

        return $memberNumber;
    }

    /**
     * Mencegah transaksi anggota tercatat dua kali.
     */
    private function ensureMemberCanBeImported(
        Member $member,
        ImportBatch $batch
    ): void {
        $hasExistingTransactions = SavingTransaction::query()
            ->where('member_id', $member->id)
            ->where(function ($query) use ($batch): void {
                $query
                    ->whereNull('import_batch_id')
                    ->orWhere(
                        'import_batch_id',
                        '!=',
                        $batch->id
                    );
            })
            ->exists();

        if ($hasExistingTransactions) {
            throw new DomainException(
                "Anggota {$member->name} sudah memiliki transaksi simpanan di aplikasi. Import dihentikan agar saldo tidak tercatat ganda."
            );
        }
    }

    /**
     * Memasukkan saldo awal dan transaksi bulanan anggota.
     *
     * @param \Illuminate\Database\Eloquent\Collection<int, ImportRow> $rows
     *
     * @param array{
     *     principal:SavingType,
     *     mandatory:SavingType,
     *     voluntary:SavingType
     * } $savingTypes
     */
    private function importMemberSavings(
        ImportBatch $batch,
        ImportMemberMapping $mapping,
        Member $member,
        $rows,
        array $savingTypes,
        ?int $userId
    ): int {
        $transactionCount = 0;

        $principalBalance = 0.0;
        $mandatoryBalance = 0.0;
        $voluntaryBalance = 0.0;

        /** @var ImportRow $firstRow */
        $firstRow = $rows->first();

        $openingDate = Carbon::parse(
            $firstRow->period_date
        )
            ->startOfMonth()
            ->subDay();

        /*
         * Saldo awal simpanan wajib:
         *
         * Saldo akhir bulan pertama
         * dikurangi setoran pada bulan pertama.
         */
        $mandatoryOpening = $this->money(
            (float) $firstRow->mandatory_balance
            - (float) $firstRow->mandatory_saving
        );

        /*
         * Saldo awal simpanan sukarela:
         *
         * Saldo akhir bulan pertama
         * dikurangi setoran
         * ditambah penarikan bulan pertama.
         */
        $voluntaryOpening = $this->money(
            (float) $firstRow->voluntary_balance
            - (float) $firstRow->voluntary_saving
            + (float) $firstRow->voluntary_withdrawal
        );

        if ($mandatoryOpening < 0) {
            throw new DomainException(
                "Saldo awal simpanan wajib {$mapping->canonical_name} menghasilkan nilai negatif."
            );
        }

        if ($voluntaryOpening < 0) {
            throw new DomainException(
                "Saldo awal simpanan sukarela {$mapping->canonical_name} menghasilkan nilai negatif."
            );
        }

        /*
         * Membuat saldo awal simpanan wajib.
         */
        if ($mandatoryOpening > 0) {
            $mandatoryBalance = $this->createTransaction(
                batch: $batch,
                row: $firstRow,
                member: $member,
                savingType: $savingTypes['mandatory'],
                userId: $userId,
                transactionDate: $openingDate,
                transactionType: 'deposit',
                amount: $mandatoryOpening,
                currentBalance: $mandatoryBalance,
                component: 'mandatory_opening',
                description: 'Saldo awal simpanan wajib'
            );

            $transactionCount++;
        }

        /*
         * Membuat saldo awal simpanan sukarela.
         */
        if ($voluntaryOpening > 0) {
            $voluntaryBalance = $this->createTransaction(
                batch: $batch,
                row: $firstRow,
                member: $member,
                savingType: $savingTypes['voluntary'],
                userId: $userId,
                transactionDate: $openingDate,
                transactionType: 'deposit',
                amount: $voluntaryOpening,
                currentBalance: $voluntaryBalance,
                component: 'voluntary_opening',
                description: 'Saldo awal simpanan sukarela'
            );

            $transactionCount++;
        }

        foreach ($rows as $row) {
            $transactionDate = Carbon::parse(
                $row->period_date
            );

            $this->ensureNonNegativeRow($row);

            /*
             * Setoran simpanan pokok.
             */
            if ((float) $row->principal_saving > 0) {
                $principalBalance = $this->createTransaction(
                    batch: $batch,
                    row: $row,
                    member: $member,
                    savingType: $savingTypes['principal'],
                    userId: $userId,
                    transactionDate: $transactionDate,
                    transactionType: 'deposit',
                    amount: (float) $row->principal_saving,
                    currentBalance: $principalBalance,
                    component: 'principal_deposit',
                    description: 'Setoran simpanan pokok'
                );

                $transactionCount++;
            }

            /*
             * Setoran simpanan wajib.
             */
            if ((float) $row->mandatory_saving > 0) {
                $mandatoryBalance = $this->createTransaction(
                    batch: $batch,
                    row: $row,
                    member: $member,
                    savingType: $savingTypes['mandatory'],
                    userId: $userId,
                    transactionDate: $transactionDate,
                    transactionType: 'deposit',
                    amount: (float) $row->mandatory_saving,
                    currentBalance: $mandatoryBalance,
                    component: 'mandatory_deposit',
                    description: 'Setoran simpanan wajib'
                );

                $transactionCount++;
            }

            /*
             * Menyamakan saldo simpanan wajib aplikasi
             * dengan saldo kumulatif dari Excel.
             */
            [
                $mandatoryBalance,
                $mandatoryAdjustmentCount,
            ] = $this->reconcileBalance(
                batch: $batch,
                row: $row,
                member: $member,
                savingType: $savingTypes['mandatory'],
                userId: $userId,
                transactionDate: $transactionDate,
                currentBalance: $mandatoryBalance,
                expectedBalance: (float) $row->mandatory_balance,
                component: 'mandatory_adjustment',
                description: 'Penyesuaian saldo simpanan wajib'
            );

            $transactionCount += $mandatoryAdjustmentCount;

            /*
             * Setoran simpanan sukarela.
             */
            if ((float) $row->voluntary_saving > 0) {
                $voluntaryBalance = $this->createTransaction(
                    batch: $batch,
                    row: $row,
                    member: $member,
                    savingType: $savingTypes['voluntary'],
                    userId: $userId,
                    transactionDate: $transactionDate,
                    transactionType: 'deposit',
                    amount: (float) $row->voluntary_saving,
                    currentBalance: $voluntaryBalance,
                    component: 'voluntary_deposit',
                    description: 'Setoran simpanan sukarela'
                );

                $transactionCount++;
            }

            $voluntaryWithdrawal = $this->money(
                (float) $row->voluntary_withdrawal
            );

            $expectedVoluntaryBalance = $this->money(
                (float) $row->voluntary_balance
            );

            /*
             * File client hanya berupa rekap bulanan.
             * Urutan transaksi sebenarnya dalam satu bulan
             * tidak diketahui.
             *
             * Sebelum penarikan, saldo direkonsiliasi menjadi:
             *
             * saldo akhir Excel + nominal penarikan.
             *
             * Setelah penarikan dilakukan, saldo akan kembali
             * sama dengan saldo akhir yang tercatat di Excel.
             */
            if ($voluntaryWithdrawal > 0) {
                $requiredBalanceBeforeWithdrawal = $this->money(
                    $expectedVoluntaryBalance
                    + $voluntaryWithdrawal
                );

                [
                    $voluntaryBalance,
                    $preWithdrawalAdjustmentCount,
                ] = $this->reconcileBalance(
                    batch: $batch,
                    row: $row,
                    member: $member,
                    savingType: $savingTypes['voluntary'],
                    userId: $userId,
                    transactionDate: $transactionDate,
                    currentBalance: $voluntaryBalance,
                    expectedBalance: $requiredBalanceBeforeWithdrawal,
                    component: 'voluntary_pre_withdrawal_adjustment',
                    description: 'Penyesuaian saldo sebelum penarikan sukarela'
                );

                $transactionCount += $preWithdrawalAdjustmentCount;

                $voluntaryBalance = $this->createTransaction(
                    batch: $batch,
                    row: $row,
                    member: $member,
                    savingType: $savingTypes['voluntary'],
                    userId: $userId,
                    transactionDate: $transactionDate,
                    transactionType: 'withdrawal',
                    amount: $voluntaryWithdrawal,
                    currentBalance: $voluntaryBalance,
                    component: 'voluntary_withdrawal',
                    description: 'Penarikan simpanan sukarela'
                );

                $transactionCount++;
            }

            /*
             * Pemeriksaan akhir saldo sukarela.
             * Saldo aplikasi harus sama dengan saldo Excel.
             */
            [
                $voluntaryBalance,
                $voluntaryAdjustmentCount,
            ] = $this->reconcileBalance(
                batch: $batch,
                row: $row,
                member: $member,
                savingType: $savingTypes['voluntary'],
                userId: $userId,
                transactionDate: $transactionDate,
                currentBalance: $voluntaryBalance,
                expectedBalance: $expectedVoluntaryBalance,
                component: 'voluntary_adjustment',
                description: 'Penyesuaian saldo akhir simpanan sukarela'
            );

            $transactionCount += $voluntaryAdjustmentCount;

            $row->update([
                'status' => 'imported',
                'message' => null,
            ]);
        }

        return $transactionCount;
    }

    /**
     * Memastikan data nominal tidak mengandung angka negatif.
     */
    private function ensureNonNegativeRow(
        ImportRow $row
    ): void {
        $fields = [
            'principal_saving' => 'simpanan pokok',
            'mandatory_saving' => 'simpanan wajib',
            'mandatory_balance' => 'saldo wajib',
            'voluntary_saving' => 'simpanan sukarela',
            'voluntary_balance' => 'saldo sukarela',
            'voluntary_withdrawal' => 'penarikan sukarela',
        ];

        foreach ($fields as $field => $label) {
            if ((float) $row->{$field} < 0) {
                throw new DomainException(
                    sprintf(
                        'Nilai %s negatif ditemukan pada sheet %s baris %d.',
                        $label,
                        $row->sheet_name,
                        $row->row_number
                    )
                );
            }
        }
    }

    /**
     * Membuat transaksi penyesuaian agar saldo aplikasi
     * sama dengan saldo yang tertulis di Excel.
     *
     * @return array{0:float,1:int}
     */
    private function reconcileBalance(
        ImportBatch $batch,
        ImportRow $row,
        Member $member,
        SavingType $savingType,
        ?int $userId,
        Carbon $transactionDate,
        float $currentBalance,
        float $expectedBalance,
        string $component,
        string $description
    ): array {
        $currentBalance = $this->money(
            $currentBalance
        );

        $expectedBalance = $this->money(
            $expectedBalance
        );

        $difference = $this->money(
            $expectedBalance - $currentBalance
        );

        if (abs($difference) < 0.01) {
            return [
                $currentBalance,
                0,
            ];
        }

        $transactionType = $difference > 0
            ? 'deposit'
            : 'withdrawal';

        $newBalance = $this->createTransaction(
            batch: $batch,
            row: $row,
            member: $member,
            savingType: $savingType,
            userId: $userId,
            transactionDate: $transactionDate,
            transactionType: $transactionType,
            amount: abs($difference),
            currentBalance: $currentBalance,
            component: $component,
            description: $description
        );

        return [
            $newBalance,
            1,
        ];
    }

    /**
     * Menyimpan satu transaksi simpanan hasil migrasi.
     */
    private function createTransaction(
        ImportBatch $batch,
        ImportRow $row,
        Member $member,
        SavingType $savingType,
        ?int $userId,
        Carbon $transactionDate,
        string $transactionType,
        float $amount,
        float $currentBalance,
        string $component,
        string $description
    ): float {
        $amount = $this->money(
            $amount
        );

        $currentBalance = $this->money(
            $currentBalance
        );

        if ($amount <= 0) {
            return $currentBalance;
        }

        $balanceAfter = $transactionType === 'deposit'
            ? $currentBalance + $amount
            : $currentBalance - $amount;

        $balanceAfter = $this->money(
            $balanceAfter
        );

        if ($balanceAfter < 0) {
            throw new DomainException(
                sprintf(
                    'Saldo %s milik %s menjadi negatif pada sheet %s baris %d.',
                    $savingType->name,
                    $member->name,
                    $row->sheet_name,
                    $row->row_number
                )
            );
        }

        SavingTransaction::create([
            'transaction_code' => $this->transactionCode(
                $batch,
                $row,
                $component
            ),
            'member_id' => $member->id,
            'saving_type_id' => $savingType->id,
            'user_id' => $userId,
            'import_batch_id' => $batch->id,
            'import_row_id' => $row->id,
            'import_component' => $component,
            'transaction_date' => $transactionDate->toDateString(),
            'transaction_type' => $transactionType,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'notes' => sprintf(
                '%s. Migrasi dari %s, sheet %s, baris %d.',
                $description,
                $batch->original_name,
                $row->sheet_name,
                $row->row_number
            ),
        ]);

        return $balanceAfter;
    }

    /**
     * Membuat kode transaksi migrasi yang unik.
     */
    private function transactionCode(
        ImportBatch $batch,
        ImportRow $row,
        string $component
    ): string {
        $componentCode = strtoupper(
            preg_replace(
                '/[^A-Z0-9]+/i',
                '-',
                $component
            ) ?: 'DATA'
        );

        return sprintf(
            'MIG-SAV-%06d-%06d-%s',
            $batch->id,
            $row->id,
            substr(
                $componentCode,
                0,
                30
            )
        );
    }

    /**
     * Membulatkan nominal uang menjadi dua angka desimal.
     */
    private function money(
        float $value
    ): float {
        return round(
            $value,
            2
        );
    }
}
