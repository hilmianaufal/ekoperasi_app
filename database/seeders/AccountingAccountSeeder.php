<?php

namespace Database\Seeders;

use App\Models\AccountingAccount;
use App\Models\AccountingAccountMapping;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AccountingAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $headers = [
                [
                    'code' => '1000',
                    'name' => 'ASET',
                    'type' => 'asset',
                    'normal_balance' => 'debit',
                ],
                [
                    'code' => '2000',
                    'name' => 'LIABILITAS',
                    'type' => 'liability',
                    'normal_balance' => 'credit',
                ],
                [
                    'code' => '3000',
                    'name' => 'EKUITAS',
                    'type' => 'equity',
                    'normal_balance' => 'credit',
                ],
                [
                    'code' => '4000',
                    'name' => 'PENDAPATAN',
                    'type' => 'revenue',
                    'normal_balance' => 'credit',
                ],
                [
                    'code' => '5000',
                    'name' => 'BEBAN',
                    'type' => 'expense',
                    'normal_balance' => 'debit',
                ],
            ];

            foreach ($headers as $header) {
                AccountingAccount::updateOrCreate(
                    [
                        'code' => $header['code'],
                    ],
                    [
                        ...$header,
                        'parent_id' => null,
                        'is_header' => true,
                        'is_active' => true,
                        'allow_manual_entries' => false,
                    ]
                );
            }

            $accounts = [
                [
                    'code' => '1100',
                    'name' => 'Kas',
                    'type' => 'asset',
                    'normal_balance' => 'debit',
                    'parent_code' => '1000',
                ],
                [
                    'code' => '1110',
                    'name' => 'Bank',
                    'type' => 'asset',
                    'normal_balance' => 'debit',
                    'parent_code' => '1000',
                ],
                [
                    'code' => '1200',
                    'name' => 'Piutang Pembiayaan',
                    'type' => 'asset',
                    'normal_balance' => 'debit',
                    'parent_code' => '1000',
                ],
                [
                    'code' => '1300',
                    'name' => 'Simpanan pada Sekunder',
                    'type' => 'asset',
                    'normal_balance' => 'debit',
                    'parent_code' => '1000',
                ],
                [
                    'code' => '1400',
                    'name' => 'Aset Tetap',
                    'type' => 'asset',
                    'normal_balance' => 'debit',
                    'parent_code' => '1000',
                ],
                [
                    'code' => '1490',
                    'name' => 'Akumulasi Penyusutan',
                    'type' => 'asset',
                    'normal_balance' => 'credit',
                    'parent_code' => '1000',
                ],

                [
                    'code' => '2100',
                    'name' => 'Simpanan Sukarela',
                    'type' => 'liability',
                    'normal_balance' => 'credit',
                    'parent_code' => '2000',
                ],
                [
                    'code' => '2200',
                    'name' => 'Utang SHU Anggota',
                    'type' => 'liability',
                    'normal_balance' => 'credit',
                    'parent_code' => '2000',
                ],
                [
                    'code' => '2300',
                    'name' => 'Liabilitas Lain',
                    'type' => 'liability',
                    'normal_balance' => 'credit',
                    'parent_code' => '2000',
                ],

                [
                    'code' => '3100',
                    'name' => 'Simpanan Pokok',
                    'type' => 'equity',
                    'normal_balance' => 'credit',
                    'parent_code' => '3000',
                ],
                [
                    'code' => '3200',
                    'name' => 'Simpanan Wajib',
                    'type' => 'equity',
                    'normal_balance' => 'credit',
                    'parent_code' => '3000',
                ],
                [
                    'code' => '3300',
                    'name' => 'Hibah',
                    'type' => 'equity',
                    'normal_balance' => 'credit',
                    'parent_code' => '3000',
                ],
                [
                    'code' => '3400',
                    'name' => 'Cadangan Koperasi',
                    'type' => 'equity',
                    'normal_balance' => 'credit',
                    'parent_code' => '3000',
                ],
                [
                    'code' => '3500',
                    'name' => 'SHU Tahun Berjalan',
                    'type' => 'equity',
                    'normal_balance' => 'credit',
                    'parent_code' => '3000',
                ],
                [
                    'code' => '3600',
                    'name' => 'Ekuitas Lain',
                    'type' => 'equity',
                    'normal_balance' => 'credit',
                    'parent_code' => '3000',
                ],

                [
                    'code' => '4100',
                    'name' => 'Pendapatan Bagi Hasil',
                    'type' => 'revenue',
                    'normal_balance' => 'credit',
                    'parent_code' => '4000',
                ],
                [
                    'code' => '4200',
                    'name' => 'Pendapatan Administrasi',
                    'type' => 'revenue',
                    'normal_balance' => 'credit',
                    'parent_code' => '4000',
                ],
                [
                    'code' => '4300',
                    'name' => 'Pendapatan Lain',
                    'type' => 'revenue',
                    'normal_balance' => 'credit',
                    'parent_code' => '4000',
                ],

                [
                    'code' => '5100',
                    'name' => 'Beban Transportasi',
                    'type' => 'expense',
                    'normal_balance' => 'debit',
                    'parent_code' => '5000',
                ],
                [
                    'code' => '5200',
                    'name' => 'Beban Operasional',
                    'type' => 'expense',
                    'normal_balance' => 'debit',
                    'parent_code' => '5000',
                ],
                [
                    'code' => '5300',
                    'name' => 'Beban Penyusutan',
                    'type' => 'expense',
                    'normal_balance' => 'debit',
                    'parent_code' => '5000',
                ],
                [
                    'code' => '5400',
                    'name' => 'Beban Lain',
                    'type' => 'expense',
                    'normal_balance' => 'debit',
                    'parent_code' => '5000',
                ],
            ];

            foreach ($accounts as $accountData) {
                $parent = AccountingAccount::query()
                    ->where(
                        'code',
                        $accountData['parent_code']
                    )
                    ->firstOrFail();

                unset(
                    $accountData['parent_code']
                );

                AccountingAccount::updateOrCreate(
                    [
                        'code' => $accountData['code'],
                    ],
                    [
                        ...$accountData,
                        'parent_id' => $parent->id,
                        'is_header' => false,
                        'is_active' => true,
                        'allow_manual_entries' => true,
                    ]
                );
            }

            $mappings = [
                'cash' => [
                    '1100',
                    'Akun kas utama.',
                ],
                'bank' => [
                    '1110',
                    'Akun rekening bank koperasi.',
                ],
                'financing_receivable' => [
                    '1200',
                    'Piutang pokok pembiayaan anggota.',
                ],
                'secondary_savings' => [
                    '1300',
                    'Simpanan koperasi pada sekunder.',
                ],
                'fixed_assets' => [
                    '1400',
                    'Aset tetap koperasi.',
                ],
                'accumulated_depreciation' => [
                    '1490',
                    'Akumulasi penyusutan aset tetap.',
                ],
                'voluntary_savings' => [
                    '2100',
                    'Simpanan sukarela anggota.',
                ],
                'shu_payable' => [
                    '2200',
                    'SHU yang menjadi hak anggota.',
                ],
                'other_liabilities' => [
                    '2300',
                    'Liabilitas lain koperasi.',
                ],
                'principal_savings' => [
                    '3100',
                    'Simpanan pokok anggota.',
                ],
                'mandatory_savings' => [
                    '3200',
                    'Simpanan wajib anggota.',
                ],
                'grant' => [
                    '3300',
                    'Hibah yang diterima koperasi.',
                ],
                'reserve' => [
                    '3400',
                    'Cadangan koperasi.',
                ],
                'current_shu' => [
                    '3500',
                    'SHU tahun berjalan.',
                ],
                'other_equity' => [
                    '3600',
                    'Ekuitas lainnya.',
                ],
                'profit_share_revenue' => [
                    '4100',
                    'Pendapatan bagi hasil pembiayaan.',
                ],
                'administration_revenue' => [
                    '4200',
                    'Pendapatan administrasi.',
                ],
                'other_revenue' => [
                    '4300',
                    'Pendapatan selain bagi hasil dan administrasi.',
                ],
                'transport_expense' => [
                    '5100',
                    'Beban transportasi.',
                ],
                'operating_expense' => [
                    '5200',
                    'Beban operasional koperasi.',
                ],
                'depreciation_expense' => [
                    '5300',
                    'Beban penyusutan aset.',
                ],
                'other_expense' => [
                    '5400',
                    'Beban lainnya.',
                ],
            ];

            foreach (
                $mappings as $mappingKey => [
                    $accountCode,
                    $description,
                ]
            ) {
                $account = AccountingAccount::query()
                    ->where('code', $accountCode)
                    ->firstOrFail();

                AccountingAccountMapping::updateOrCreate(
                    [
                        'mapping_key' => $mappingKey,
                    ],
                    [
                        'accounting_account_id' =>
                            $account->id,

                        'description' =>
                            $description,
                    ]
                );
            }
        });
    }
}
