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
    Schema::create('orden_compras', function (Blueprint $table) {
        $table->id();
        $table->foreignId('proveedor_id')->constrained('proveedores');
        $table->foreignId('sucursal_id')->constrained('sucursales'); // Para saber a qué local va la mercadería
        $table->foreignId('user_id')->constrained('users'); // Quién armó el pedido
        
        $table->string('nro_comprobante')->nullable(); // Por si el proveedor nos da un número de seguimiento
        $table->date('fecha_emision')->default(now());
        $table->date('fecha_entrega_esperada')->nullable();
        
        // Los estados que definiste en tu documentación
        $table->enum('estado', ['Sugerida', 'Borrador', 'Enviada', 'Recepcionada', 'Cancelada'])->default('Borrador');
        
        $table->decimal('total_estimado', 12, 2)->default(0);
        $table->text('observaciones')->nullable();
        
        $table->timestamps();
        $table->softDeletes(); // Siempre cuidando el historial
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compras');
    }
};
