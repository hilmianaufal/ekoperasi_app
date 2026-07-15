<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('installment_payments', function (Blueprint $table) {
            $table->id();

            $table->string('payment_code')
                ->nullable()
                ->unique();

            $table->foreignId('loan_installment_id')
                ->constrained('loan_installments')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->date('payment_date');

            $table->decimal('amount', 15, 2);

            $table->decimal('remaining_after', 15, 2)
                ->default(0);

            $table->enum('payment_method', [
                'cash',
                'transfer',
                'other',
            ])->default('cash');

            $table->string('reference_number')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index([
                'payment_date',
                'payment_method',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('installment_payments');
    }
};
