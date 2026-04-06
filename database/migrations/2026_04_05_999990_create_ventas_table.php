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
        Schema::create('ventas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('turno_caja_id')->constrained('turno_cajas');
            $table->foreignId('consumidor_id')->nullable()->constrained('consumidores'); 
            $table->string('metodo_pago'); 
            $table->decimal('total', 12, 2);
            $table->enum('estado', ['Completada', 'Cancelada'])->default('Completada');
            $table->string('motivo_anulacion')->nullable(); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ventas');
    }
};
