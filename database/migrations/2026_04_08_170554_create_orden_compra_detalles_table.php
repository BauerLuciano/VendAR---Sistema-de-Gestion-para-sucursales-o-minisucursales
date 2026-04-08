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
    Schema::create('orden_compra_detalles', function (Blueprint $table) {
        $table->id();
        $table->foreignId('orden_compra_id')->constrained('orden_compras')->cascadeOnDelete();
        $table->foreignId('producto_id')->constrained('productos');
        
        $table->integer('cantidad_pedida');
        $table->integer('cantidad_recibida')->default(0); // Clave para cuando llegue el camión y controlemos
        
        $table->decimal('costo_unitario_estimado', 12, 2); // Lo que creemos que nos va a salir
        $table->decimal('subtotal_estimado', 12, 2);
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_compra_detalles');
    }
};
