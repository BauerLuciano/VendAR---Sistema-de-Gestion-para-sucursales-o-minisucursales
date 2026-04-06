<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimiento_cajas', function (Blueprint $table) {
            $table->id();
            // Vinculamos el movimiento al turno actual
            $table->foreignId('turno_caja_id')->constrained('turno_cajas')->restrictOnDelete();
            
            // INGRESO o EGRESO
            $table->enum('tipo', ['INGRESO', 'EGRESO']); 
            
            // GASTO_OPERATIVO, RETIRO_SOCIO, VENTA, etc.
            $table->string('concepto'); 
            
            // EFECTIVO, MERCADO_PAGO, TRANSFERENCIA
            $table->string('metodo_pago'); 
            
            $table->decimal('monto', 10, 2);
            $table->string('descripcion')->nullable(); // Ej: "Compra de yerba", "Pago a Coca-Cola"
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimiento_cajas');
    }
};