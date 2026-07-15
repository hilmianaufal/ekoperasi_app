<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            $table->string('loan_number')
                ->nullable()
                ->unique();

            $table->foreignId('member_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('application_date');

            $table->decimal('principal_amount', 15, 2);
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->unsignedSmallInteger('tenor_months');

            $table->decimal('interest_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('monthly_installment', 15, 2)->default(0);

            $table->date('start_date')->nullable();
            $table->date('maturity_date')->nullable();

            $table->text('purpose');
            $table->text('notes')->nullable();

            $table->enum('status', [
                'pending',
                'active',
                'rejected',
                'paid',
                'cancelled',
            ])->default('pending');

            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();

            $table->index([
                'member_id',
                'status',
                'application_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
