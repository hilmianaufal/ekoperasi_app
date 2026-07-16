<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shu_periods', function (Blueprint $table) {
            $table->id();

            $table->string('code')
                ->nullable()
                ->unique();

            $table->unsignedSmallInteger('year')
                ->unique();

            $table->date('calculation_date');

            $table->decimal(
                'business_service_rate',
                8,
                4
            )->default(18);

            $table->decimal(
                'saving_service_rate',
                8,
                4
            )->default(6);

            /*
             * Total SHU berdasarkan laporan keuangan/RAT.
             */
            $table->decimal(
                'declared_total_shu',
                15,
                2
            )->default(0);

            /*
             * Bagian SHU yang dialokasikan kepada anggota.
             */
            $table->decimal(
                'declared_member_shu',
                15,
                2
            )->default(0);

            $table->decimal(
                'declared_business_service',
                15,
                2
            )->default(0);

            $table->decimal(
                'declared_saving_service',
                15,
                2
            )->default(0);

            $table->enum('status', [
                'draft',
                'review',
                'approved',
                'distributed',
            ])->default('draft');

            $table->text('notes')
                ->nullable();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('approved_at')
                ->nullable();

            $table->timestamps();

            $table->index(
                ['status', 'year'],
                'shu_period_status_year_idx'
            );
        });

        Schema::create('shu_import_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shu_period_id')
                ->constrained('shu_periods')
                ->cascadeOnDelete();

            $table->string('code')
                ->nullable()
                ->unique();

            $table->string('original_name');
            $table->string('stored_path');

            $table->char('file_hash', 64)
                ->index();

            $table->enum('status', [
                'uploaded',
                'previewed',
                'processing',
                'completed',
                'failed',
                'cancelled',
            ])->default('uploaded');

            $table->unsignedInteger('row_count')
                ->default(0);

            $table->unsignedInteger('matched_count')
                ->default(0);

            $table->unsignedInteger('review_count')
                ->default(0);

            $table->unsignedInteger('imported_count')
                ->default(0);

            $table->json('warnings')
                ->nullable();

            $table->text('error_message')
                ->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('processed_at')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'shu_period_id',
                    'file_hash',
                ],
                'shu_import_period_file_unique'
            );
        });

        Schema::create('shu_import_rows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shu_import_batch_id')
                ->constrained('shu_import_batches')
                ->cascadeOnDelete();

            $table->string('sheet_name');
            $table->unsignedSmallInteger('row_number');

            $table->unsignedSmallInteger('source_number')
                ->nullable();

            $table->string('source_name');
            $table->string('normalized_name');

            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            $table->decimal(
                'receivable_balance',
                15,
                2
            )->default(0);

            $table->decimal(
                'profit_share_base',
                15,
                2
            )->default(0);

            $table->decimal(
                'principal_saving',
                15,
                2
            )->default(0);

            $table->decimal(
                'mandatory_saving',
                15,
                2
            )->default(0);

            $table->decimal(
                'saving_balance',
                15,
                2
            )->default(0);

            /*
             * Nilai yang tertulis pada file client.
             */
            $table->decimal(
                'source_business_service',
                15,
                2
            )->default(0);

            $table->decimal(
                'source_saving_service',
                15,
                2
            )->default(0);

            $table->decimal(
                'source_total_shu',
                15,
                2
            )->default(0);

            /*
             * Nilai hasil hitung ulang aplikasi.
             */
            $table->decimal(
                'calculated_business_service',
                15,
                2
            )->default(0);

            $table->decimal(
                'calculated_saving_service',
                15,
                2
            )->default(0);

            $table->decimal(
                'calculated_total_shu',
                15,
                2
            )->default(0);

            $table->decimal(
                'difference',
                15,
                2
            )->default(0);

            $table->enum('status', [
                'new',
                'matched',
                'review',
                'ignored',
                'imported',
            ])->default('new');

            $table->json('raw_data')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'shu_import_batch_id',
                    'sheet_name',
                    'row_number',
                ],
                'shu_import_row_unique'
            );

            $table->index(
                [
                    'shu_import_batch_id',
                    'source_number',
                ],
                'shu_import_source_idx'
            );
        });

        Schema::create('shu_member_allocations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shu_period_id')
                ->constrained('shu_periods')
                ->cascadeOnDelete();

            $table->foreignId('shu_import_row_id')
                ->nullable()
                ->constrained('shu_import_rows')
                ->nullOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            $table->unsignedSmallInteger('source_number')
                ->nullable();

            $table->decimal(
                'receivable_balance',
                15,
                2
            )->default(0);

            $table->decimal(
                'profit_share_base',
                15,
                2
            )->default(0);

            $table->decimal(
                'principal_saving',
                15,
                2
            )->default(0);

            $table->decimal(
                'mandatory_saving',
                15,
                2
            )->default(0);

            $table->decimal(
                'saving_balance',
                15,
                2
            )->default(0);

            $table->decimal(
                'business_service_amount',
                15,
                2
            )->default(0);

            $table->decimal(
                'saving_service_amount',
                15,
                2
            )->default(0);

            $table->decimal(
                'total_shu',
                15,
                2
            )->default(0);

            $table->decimal(
                'paid_amount',
                15,
                2
            )->default(0);

            $table->enum('payment_status', [
                'unpaid',
                'partial',
                'paid',
            ])->default('unpaid');

            $table->timestamp('paid_at')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'shu_period_id',
                    'member_id',
                ],
                'shu_period_member_unique'
            );

            $table->unique(
                'shu_import_row_id',
                'shu_allocation_import_row_unique'
            );
        });

        Schema::create('shu_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shu_member_allocation_id')
                ->constrained('shu_member_allocations')
                ->restrictOnDelete();

            $table->string('payment_code')
                ->nullable()
                ->unique();

            $table->date('payment_date');

            $table->decimal(
                'amount',
                15,
                2
            );

            $table->enum('payment_method', [
                'cash',
                'transfer',
                'other',
            ])->default('cash');

            $table->string('reference_number')
                ->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index(
                [
                    'payment_date',
                    'payment_method',
                ],
                'shu_payment_date_method_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shu_payments');
        Schema::dropIfExists('shu_member_allocations');
        Schema::dropIfExists('shu_import_rows');
        Schema::dropIfExists('shu_import_batches');
        Schema::dropIfExists('shu_periods');
    }
};
