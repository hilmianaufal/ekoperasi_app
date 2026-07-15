<?php

namespace Database\Seeders;

use App\Models\SavingType;
use Illuminate\Database\Seeder;

class SavingTypeSeeder extends Seeder
{
    public function run(): void
    {
        $savingTypes = [
            [
                'name' => 'Simpanan Pokok',
                'code' => 'POKOK',
                'description' => 'Simpanan awal yang dibayarkan ketika anggota bergabung.',
                'default_amount' => 100000,
                'is_withdrawable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Simpanan Wajib',
                'code' => 'WAJIB',
                'description' => 'Simpanan rutin yang wajib dibayarkan oleh anggota.',
                'default_amount' => 50000,
                'is_withdrawable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'Simpanan Sukarela',
                'code' => 'SUKARELA',
                'description' => 'Simpanan fleksibel yang dapat disetor dan ditarik anggota.',
                'default_amount' => 0,
                'is_withdrawable' => true,
                'is_active' => true,
            ],
        ];

        foreach ($savingTypes as $savingType) {
            SavingType::updateOrCreate(
                ['code' => $savingType['code']],
                $savingType
            );
        }
    }
}
