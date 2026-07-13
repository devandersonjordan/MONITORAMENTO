<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('logo_path')->nullable();
            $table->string('cnpj', 18)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('email');
            $table->string('plan')->default('basic');
            $table->integer('max_clients')->default(50);
            $table->integer('max_plants')->default(100);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
