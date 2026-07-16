<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_import_batches', function (Blueprint $table) {
            $table->id();

            $table->string('code')
                ->nullable()
                ->unique();

            $table->foreignId('data_import_batch_id')
                ->constrained('import_batches')
                ->restrictOnDelete();

            $table->string('original_name');
            $table->string('stored_path');

            $table->char('file_hash', 64)
                ->index();

            $table->date('cutoff_date');

            $table->enum('status', [
                'uploaded',
                'previewed',
                'processing',
                'completed',
                'failed',
                'cancelled',
            ])->default('uploaded');

            $table->unsignedInteger('sheet_count')
                ->default(0);

            $table->unsignedInteger('row_count')
                ->default(0);

            $table->unsignedInteger('imported_cash_count')
                ->default(0);

            $table->json('warnings')
                ->nullable();

            $table->text('error_message')
                ->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamp('processed_at')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'data_import_batch_id',
                    'file_hash',
                ],
                'cash_import_file_unique'
            );

            $table->index(
                [
                    'status',
                    'created_at',
                ],
                'cash_import_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_import_batches');
    }
};
