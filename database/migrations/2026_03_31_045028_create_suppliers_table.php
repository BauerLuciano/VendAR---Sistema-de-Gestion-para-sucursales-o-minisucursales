<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
        {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->id();
                $table->string('business_name'); // Razón social
                $table->string('cuit')->unique(); // CUIT (único para no tener repetidos)
                $table->string('phone')->nullable(); // Teléfono
                $table->timestamps();
            });
        }
};
