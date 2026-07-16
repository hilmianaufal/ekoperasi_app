<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_import_rows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cash_import_batch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sheet_name');
            $table->unsignedSmallInteger('row_number');
            $table->date('period_date');

            $table->string('description')
                ->nullable();

            $table->string('income_code', 30)
                ->nullable();

            $table->decimal('financing_expense', 15, 2)
                ->default(0);

            $table->decimal('principal_refund', 15, 2)
                ->default(0);

            $table->decimal('mandatory_refund', 15, 2)
                ->default(0);

            $table->decimal('voluntary_withdrawal', 15, 2)
                ->default(0);

            $table->decimal('transport_expense', 15, 2)
                ->default(0);

            $table->decimal('other_expense', 15, 2)
                ->default(0);

            $table->decimal('installment_income', 15, 2)
                ->default(0);

            $table->decimal('profit_share_income', 15, 2)
                ->default(0);

            $table->decimal('administration_income', 15, 2)
                ->default(0);

            $table->decimal('principal_deposit', 15, 2)
                ->default(0);

            $table->decimal('mandatory_deposit', 15, 2)
                ->default(0);

            $table->decimal('voluntary_deposit', 15, 2)
                ->default(0);

            $table->json('raw_data')
                ->nullable();

            $table->enum('status', [
                'pending',
                'ready',
                'imported',
                'skipped',
                'error',
            ])->default('pending');

            $table->text('message')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'cash_import_batch_id',
                    'sheet_name',
                    'row_number',
                ],
                'cash_import_row_unique'
            );

            $table->index(
                [
                    'cash_import_batch_id',
                    'period_date',
                ],
                'cash_import_period_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_import_rows');
    }
};
