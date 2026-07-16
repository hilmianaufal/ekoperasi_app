<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'import_member_mappings',
            function (Blueprint $table) {
                $table->id();

                $table->foreignId('import_batch_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->unsignedSmallInteger('source_number');

                $table->json('detected_names');

                $table->string('canonical_name');
                $table->string('normalized_name');

                $table->foreignId('member_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete();

                $table->enum('status', [
                    'new',
                    'matched',
                    'review',
                    'ignored',
                    'imported',
                ])->default('new');

                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(
                    ['import_batch_id', 'source_number'],
                    'import_mapping_source_unique'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('import_member_mappings');
    }
};
