<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inverters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plant_id')->constrained('plants')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->string('brand');
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable();
            $table->text('api_credentials')->nullable();
            $table->string('status')->default('online');
            $table->timestamp('last_communication_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('plant_id');
            $table->index('company_id');
            $table->index('brand');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inverters');
    }
};
