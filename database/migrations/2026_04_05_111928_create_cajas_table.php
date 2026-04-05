<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->onDelete('cascade');
            $table->string('nombre'); // Ej: "Caja Principal", "Caja Kiosco"
            $table->boolean('estado')->default(true); // Activa o inactiva (rota)
            $table->timestamps();
        });
    }

    
    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};
