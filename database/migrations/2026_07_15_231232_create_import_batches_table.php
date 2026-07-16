<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table) {
            $table->id();

            $table->string('code')
                ->nullable()
                ->unique();

            $table->string('type')
                ->default('installment_recap');

            $table->string('original_name');
            $table->string('stored_path');
            $table->char('file_hash', 64)->index();

            $table->date('cutoff_date')->nullable();

            $table->enum('status', [
                'uploaded',
                'previewed',
                'processing',
                'completed',
                'failed',
                'cancelled',
            ])->default('uploaded');

            $table->unsignedInteger('sheet_count')->default(0);
            $table->unsignedInteger('row_count')->default(0);
            $table->unsignedInteger('member_count')->default(0);

            $table->unsignedInteger('imported_member_count')->default(0);
            $table->unsignedInteger('imported_saving_count')->default(0);
            $table->unsignedInteger('imported_loan_count')->default(0);

            $table->json('warnings')->nullable();
            $table->text('error_message')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(
                ['status', 'created_at'],
                'import_batch_status_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
