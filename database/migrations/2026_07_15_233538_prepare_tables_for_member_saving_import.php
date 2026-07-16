<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * Data lama client belum memiliki jenis kelamin
         * dan tanggal bergabung masing-masing anggota.
         */
        Schema::table('members', function (Blueprint $table) {
            $table->enum('gender', [
                'male',
                'female',
            ])->nullable()->change();

            $table->date('join_date')
                ->nullable()
                ->change();
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->timestamp('members_savings_imported_at')
                ->nullable()
                ->after('completed_at');
        });

        Schema::table('saving_transactions', function (Blueprint $table) {
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

            $table->string('import_component', 60)
                ->nullable()
                ->after('import_row_id');

            $table->unique(
                [
                    'import_row_id',
                    'import_component',
                ],
                'saving_import_component_unique'
            );

            $table->index(
                [
                    'import_batch_id',
                    'member_id',
                ],
                'saving_import_member_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('saving_transactions', function (Blueprint $table) {
            $table->dropUnique(
                'saving_import_component_unique'
            );

            $table->dropIndex(
                'saving_import_member_idx'
            );

            $table->dropConstrainedForeignId(
                'import_row_id'
            );

            $table->dropConstrainedForeignId(
                'import_batch_id'
            );

            $table->dropColumn(
                'import_component'
            );
        });

        Schema::table('import_batches', function (Blueprint $table) {
            $table->dropColumn(
                'members_savings_imported_at'
            );
        });

        /*
         * Kolom gender dan join_date tidak dikembalikan
         * menjadi wajib agar data anggota hasil migrasi
         * yang belum lengkap tidak rusak.
         */
    }
};
