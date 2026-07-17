<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->decimal(
                'administration_fee',
                15,
                2
            )
                ->default(0)
                ->after('monthly_installment');

            /*
             * separate:
             * administrasi dibayar terpisah saat pencairan.
             *
             * deducted:
             * administrasi dipotong dari uang yang diterima anggota.
             */
            $table->string(
                'administration_collection_method',
                20
            )
                ->default('separate')
                ->after('administration_fee');

            $table->string(
                'administration_payment_method',
                20
            )
                ->default('cash')
                ->after('administration_collection_method');

            $table->timestamp(
                'administration_collected_at'
            )
                ->nullable()
                ->after('administration_payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table): void {
            $table->dropColumn([
                'administration_fee',
                'administration_collection_method',
                'administration_payment_method',
                'administration_collected_at',
            ]);
        });
    }
};
