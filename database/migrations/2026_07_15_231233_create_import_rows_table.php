<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table) {
            $table->id();

            $table->foreignId('import_batch_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('sheet_name');
            $table->unsignedSmallInteger('row_number');

            $table->date('period_date');

            $table->unsignedSmallInteger('source_number');
            $table->string('source_name');
            $table->string('normalized_name');
            $table->string('canonical_name')->nullable();

            $table->decimal('principal_saving', 15, 2)
                ->default(0);

            $table->decimal('mandatory_saving', 15, 2)
                ->default(0);

            $table->decimal('mandatory_balance', 15, 2)
                ->default(0);

            $table->decimal('principal_installment', 15, 2)
                ->default(0);

            $table->decimal('profit_share', 15, 2)
                ->default(0);

            $table->decimal('accumulated_profit_share', 15, 2)
                ->default(0);

            $table->decimal('remaining_financing', 15, 2)
                ->default(0);

            $table->decimal('voluntary_saving', 15, 2)
                ->default(0);

            $table->decimal('voluntary_balance', 15, 2)
                ->default(0);

            $table->decimal('voluntary_withdrawal', 15, 2)
                ->default(0);

            $table->decimal('administration_fee', 15, 2)
                ->default(0);

            $table->decimal('new_financing', 15, 2)
                ->default(0);

            $table->json('raw_data')->nullable();

            $table->enum('status', [
                'pending',
                'ready',
                'skipped',
                'imported',
                'error',
            ])->default('pending');

            $table->text('message')->nullable();
            $table->timestamps();

            $table->unique(
                [
                    'import_batch_id',
                    'sheet_name',
                    'row_number',
                ],
                'import_row_source_unique'
            );

            $table->index(
                [
                    'import_batch_id',
                    'source_number',
                    'period_date',
                ],
                'import_row_member_period_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
