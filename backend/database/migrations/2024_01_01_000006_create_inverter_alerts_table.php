<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inverter_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inverter_id')->constrained('inverters')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('type');
            $table->string('severity');
            $table->text('message');
            $table->jsonb('data')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index('inverter_id');
            $table->index('company_id');
            $table->index('severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inverter_alerts');
    }
};
