<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)
                ->unique();

            $table->string('name', 150);

            $table->enum('type', [
                'asset',
                'liability',
                'equity',
                'revenue',
                'expense',
            ]);

            $table->enum('normal_balance', [
                'debit',
                'credit',
            ]);

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('accounting_accounts')
                ->nullOnDelete();

            $table->boolean('is_header')
                ->default(false);

            $table->boolean('is_active')
                ->default(true);

            $table->boolean('allow_manual_entries')
                ->default(true);

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->index([
                'type',
                'is_active',
            ]);

            $table->index([
                'parent_id',
                'code',
            ]);
        });

        Schema::create('accounting_account_mappings', function (Blueprint $table) {
            $table->id();

            /*
             * Contoh:
             * cash
             * financing_receivable
             * principal_savings
             * profit_share_revenue
             */
            $table->string('mapping_key', 100)
                ->unique();

            $table->foreignId('accounting_account_id')
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->string('description')
                ->nullable();

            $table->timestamps();
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            $table->string('entry_number')
                ->nullable()
                ->unique();

            $table->date('entry_date');

            $table->string('reference_number')
                ->nullable();

            $table->text('description');

            /*
             * Sumber transaksi otomatis:
             *
             * saving_transaction
             * loan_disbursement
             * installment_payment
             * shu_payment
             * cash_transaction
             * opening_balance
             * adjustment
             */
            $table->string('source_type', 100)
                ->nullable();

            $table->unsignedBigInteger('source_id')
                ->nullable();

            $table->enum('status', [
                'draft',
                'posted',
                'reversed',
            ])->default('draft');

            $table->foreignId('reversal_of_id')
                ->nullable()
                ->constrained('journal_entries')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')
                ->nullable();

            $table->text('notes')
                ->nullable();

            $table->timestamps();

            $table->unique(
                [
                    'source_type',
                    'source_id',
                ],
                'journal_source_unique'
            );

            $table->index([
                'entry_date',
                'status',
            ]);
        });

        Schema::create('journal_entry_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('journal_entry_id')
                ->constrained('journal_entries')
                ->cascadeOnDelete();

            $table->foreignId('accounting_account_id')
                ->constrained('accounting_accounts')
                ->restrictOnDelete();

            $table->string('description')
                ->nullable();

            $table->decimal('debit', 18, 2)
                ->default(0);

            $table->decimal('credit', 18, 2)
                ->default(0);

            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();

            $table->foreignId('loan_id')
                ->nullable()
                ->constrained('loans')
                ->nullOnDelete();

            $table->timestamps();

            $table->index([
                'accounting_account_id',
                'journal_entry_id',
            ]);

            $table->index([
                'member_id',
                'accounting_account_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'journal_entry_lines'
        );

        Schema::dropIfExists(
            'journal_entries'
        );

        Schema::dropIfExists(
            'accounting_account_mappings'
        );

        Schema::dropIfExists(
            'accounting_accounts'
        );
    }
};
