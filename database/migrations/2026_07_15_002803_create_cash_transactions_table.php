<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_code')
                ->nullable()
                ->unique();

            $table->date('transaction_date');

            $table->enum('direction', [
                'income',
                'expense',
            ]);

            $table->string('category', 150);

            $table->decimal('amount', 15, 2);

            $table->enum('payment_method', [
                'cash',
                'transfer',
                'other',
            ])->default('cash');

            $table->text('description')->nullable();

            $table->string('source_type', 100)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();

            $table->unique([
                'source_type',
                'source_id',
            ]);

            $table->index([
                'transaction_date',
                'direction',
            ]);

            $table->index('category');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};
