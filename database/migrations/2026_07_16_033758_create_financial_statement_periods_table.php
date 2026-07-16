<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_statement_periods', function (Blueprint $table) {
            $table->id();

            $table->string('code')
                ->nullable()
                ->unique();

            $table->date('report_date')
                ->unique();

            /*
             * Saldo kas pada awal tahun laporan.
             * Pergerakan kas setelah tanggal ini dihitung
             * dari cash_transactions.
             */
            $table->decimal(
                'opening_cash_balance',
                15,
                2
            )->default(0);

            /*
             * Akun yang belum memiliki buku besar otomatis:
             *
             * bank
             * secondary_savings
             * fixed_assets
             * accumulated_depreciation
             * other_assets
             * other_liabilities
             * grant
             * reserve
             * current_shu
             * other_equity
             */
            $table->json('manual_balances')
                ->nullable();

            /*
             * Nilai pembanding dari laporan client.
             */
            $table->json('reference_balances')
                ->nullable();

            $table->enum('status', [
                'draft',
                'review',
                'approved',
            ])->default('draft');

            $table->text('notes')
                ->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamps();

            $table->index([
                'status',
                'report_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'financial_statement_periods'
        );
    }
};
