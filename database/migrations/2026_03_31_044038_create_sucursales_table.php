<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sucursales', function (Blueprint $table) {
            $table->id(); 
            $table->string('nombre'); 
            $table->string('direccion')->nullable(); 
            $table->string('telefono', 15)->nullable(); 
            $table->enum('tipo', ['punto_de_venta', 'deposito'])->default('punto_de_venta'); 
            $table->boolean('estado')->default(true); // Reemplaza a is_active
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sucursales');
    }
};