<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saving_transactions', function (Blueprint $table) {
            $table->id();

            $table->string('transaction_code')
                ->nullable()
                ->unique();

            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            $table->foreignId('saving_type_id')
                ->constrained('saving_types')
                ->restrictOnDelete();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->date('transaction_date');

            $table->enum('transaction_type', [
                'deposit',
                'withdrawal',
            ]);

            $table->decimal('amount', 15, 2);

            $table->decimal('balance_after', 15, 2)
                ->default(0);

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index(
                [
                    'member_id',
                    'saving_type_id',
                    'transaction_date',
                ],
                'saving_member_type_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saving_transactions');
    }
};
