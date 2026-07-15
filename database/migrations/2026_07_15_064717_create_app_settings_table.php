<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();

            $table->string('cooperative_name')
                ->default('e-Koperasi');

            $table->string('short_name')
                ->default('e-Koperasi');

            $table->string('tagline')
                ->nullable();

            $table->string('registration_number')
                ->nullable();

            $table->text('address')
                ->nullable();

            $table->string('phone', 30)
                ->nullable();

            $table->string('email')
                ->nullable();

            $table->string('chairman_name')
                ->nullable();

            $table->string('treasurer_name')
                ->nullable();

            $table->string('logo')
                ->nullable();

            $table->decimal(
                'default_interest_rate',
                5,
                2
            )->default(1);

            $table->unsignedSmallInteger(
                'default_tenor_months'
            )->default(12);

            $table->decimal(
                'minimum_loan_amount',
                15,
                2
            )->default(100000);

            $table->decimal(
                'maximum_loan_amount',
                15,
                2
            )->nullable();

            $table->text('receipt_footer')
                ->nullable();

            $table->string('timezone')
                ->default('Asia/Jakarta');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
