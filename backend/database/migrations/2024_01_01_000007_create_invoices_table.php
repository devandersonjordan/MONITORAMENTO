<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->date('competence');
            $table->date('due_date')->nullable();
            $table->bigInteger('amount_cents')->nullable();
            $table->decimal('consumption_kwh', 10, 2)->nullable();
            $table->decimal('injected_kwh', 10, 2)->nullable();
            $table->decimal('compensated_kwh', 10, 2)->nullable();
            $table->decimal('previous_balance_kwh', 10, 2)->nullable();
            $table->decimal('current_balance_kwh', 10, 2)->nullable();
            $table->decimal('credits_received_kwh', 10, 2)->nullable();
            $table->decimal('credits_used_kwh', 10, 2)->nullable();
            $table->decimal('tariff', 8, 6)->nullable();
            $table->string('flag')->nullable();
            $table->decimal('icms_value', 10, 2)->nullable();
            $table->decimal('pis_value', 10, 2)->nullable();
            $table->decimal('cofins_value', 10, 2)->nullable();
            $table->decimal('public_lighting_value', 10, 2)->nullable();
            $table->string('pdf_path')->nullable();
            $table->string('ocr_status')->default('pending');
            $table->jsonb('raw_ocr_data')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('client_id');
            $table->index('company_id');
            $table->index('competence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
