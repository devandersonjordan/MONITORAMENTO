<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inverter_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inverter_id')->constrained('inverters')->cascadeOnDelete();
            $table->timestamp('recorded_at');
            $table->decimal('power_w', 10, 2)->nullable();
            $table->decimal('voltage_v', 8, 2)->nullable();
            $table->decimal('current_a', 8, 2)->nullable();
            $table->decimal('frequency_hz', 6, 2)->nullable();
            $table->decimal('temperature_c', 5, 1)->nullable();
            $table->decimal('daily_kwh', 10, 2)->nullable();
            $table->decimal('monthly_kwh', 12, 2)->nullable();
            $table->decimal('yearly_kwh', 14, 2)->nullable();
            $table->decimal('total_kwh', 14, 2)->nullable();
            $table->decimal('efficiency_pct', 5, 2)->nullable();
            $table->string('status')->nullable();
            $table->timestamps();

            $table->index(['inverter_id', 'recorded_at']);
            $table->index('recorded_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inverter_readings');
    }
};
