<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('import_batches', function (Blueprint $table) {
            $table->unsignedInteger('imported_installment_count')
                ->default(0)
                ->after('imported_loan_count');

            $table->unsignedInteger('imported_payment_count')
                ->default(0)
                ->after('imported_installment_count');

            $table->unsignedInteger('imported_financing_entry_count')
                ->default(0)
                ->after('imported_payment_count');

            $table->timestamp('financing_imported_at')
                ->nullable()
                ->after('members_savings_imported_at');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->foreignId('import_batch_id')
                ->nullable()
                ->after('approved_by')
                ->constrained('import_batches')
                ->nullOnDelete();

            $table->unsignedSmallInteger('source_number')
                ->nullable()
                ->after('import_batch_id');

            $table->boolean('is_legacy')
                ->default(false)
                ->after('source_number');

            $table->decimal('opening_principal', 15, 2)
                ->default(0)
                ->after('principal_amount');

            $table->decimal('disbursed_during_import', 15, 2)
                ->default(0)
                ->after('opening_principal');

            $table->decimal('outstanding_principal', 15, 2)
                ->default(0)
                ->after('disbursed_during_import');

            $table->decimal('profit_share_paid', 15, 2)
                ->default(0)
                ->after('outstanding_principal');

            $table->decimal('administration_paid', 15, 2)
                ->default(0)
                ->after('profit_share_paid');

            $table->unique(
                ['import_batch_id', 'source_number'],
                'loan_import_source_unique'
            );
        });

        Schema::table('loan_installments', function (Blueprint $table) {
            $table->foreignId('import_batch_id')
                ->nullable()
                ->after('loan_id')
                ->constrained('import_batches')
                ->nullOnDelete();

            $table->foreignId('import_row_id')
                ->nullable()
                ->after('import_batch_id')
                ->constrained('import_rows')
                ->nullOnDelete();

            $table->decimal(
                'reported_remaining_principal',
                15,
                2
            )
                ->default(0)
                ->after('paid_amount');

            $table->unique(
                'import_row_id',
                'loan_inst_import_row_unique'
            );
        });

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->foreignId('import_batch_id')
                ->nullable()
                ->after('user_id')
                ->constrained('import_batches')
                ->nullOnDelete();

            $table->foreignId('import_row_id')
                ->nullable()
                ->after('import_batch_id')
                ->constrained('import_rows')
                ->nullOnDelete();

            $table->decimal('principal_amount', 15, 2)
                ->default(0)
                ->after('amount');

            $table->decimal('profit_share_amount', 15, 2)
                ->default(0)
                ->after('principal_amount');

            $table->decimal('administration_fee', 15, 2)
                ->default(0)
                ->after('profit_share_amount');

            $table->unique(
                'import_row_id',
                'loan_pay_import_row_unique'
            );
        });

        Schema::create('loan_import_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('import_batch_id')
                ->constrained('import_batches')
                ->cascadeOnDelete();

            $table->foreignId('import_row_id')
                ->constrained('import_rows')
                ->cascadeOnDelete();

            $table->foreignId('loan_id')
                ->constrained('loans')
                ->restrictOnDelete();

            $table->foreignId('member_id')
                ->constrained('members')
                ->restrictOnDelete();

            $table->date('period_date');

            $table->decimal('opening_principal', 15, 2)
                ->default(0);

            $table->decimal('new_financing', 15, 2)
                ->default(0);

            $table->decimal('principal_payment', 15, 2)
                ->default(0);

            $table->decimal('profit_share', 15, 2)
                ->default(0);

            $table->decimal('administration_fee', 15, 2)
                ->default(0);

            $table->decimal('reported_remaining', 15, 2)
                ->default(0);

            $table->decimal('calculated_remaining', 15, 2)
                ->default(0);

            $table->decimal('balance_adjustment', 15, 2)
                ->default(0);

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(
                'import_row_id',
                'loan_entry_import_row_unique'
            );

            $table->index(
                ['import_batch_id', 'member_id', 'period_date'],
                'loan_entry_batch_member_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_import_entries');

        Schema::table('installment_payments', function (Blueprint $table) {
            $table->dropUnique('loan_pay_import_row_unique');

            $table->dropConstrainedForeignId('import_row_id');
            $table->dropConstrainedForeignId('import_batch_id');

            $table->dropColumn([
                'principal_amount',
                'profit_share_amount',
                'administration_fee',
            ]);
        });

        Schema::table('loan_installments', function (Blueprint $table) {
            $table->dropUnique('loan_inst_import_row_unique');

            $table->dropConstrainedForeignId('import_row_id');
            $table->dropConstrainedForeignId('import_batch_id');

            $table->dropColumn('reported_remaining_principal');
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropUnique('loan_import_source_unique');

            $table->dropConstrainedForeignId('import_batch_id');

            $table->dropColumn([
                'source_number',
                'is_legacy',
                'opening_principal',
                'disbursed_during_import',
                'outstanding_principal',
                'profit_share_paid',
                'administration_paid',
            ]);
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn([
                'imported_installment_count',
                'imported_payment_count',
                'imported_financing_entry_count',
                'financing_imported_at',
            ]);
        });
    }
};
