<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('power_kwp', 10, 2);
            $table->date('installation_date');
            $table->string('module_model')->nullable();
            $table->integer('module_qty')->nullable();
            $table->string('inverter_model')->nullable();
            $table->decimal('inverter_power_kw', 8, 2)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('address')->nullable();
            $table->string('installer_company')->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index('client_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
