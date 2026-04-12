<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. PASAMOS EL STOCK A DECIMALES (Para soportar Kg y Gramos)
        Schema::table('producto_sucursal', function (Blueprint $table) {
            // Decimal 10,3 significa: 10 números en total, 3 después de la coma (Ej: 1500.500 kg)
            $table->decimal('cantidad_fisica', 10, 3)->default(0)->change();
            $table->decimal('cantidad_reservada', 10, 3)->default(0)->change();
        });

        // 2. CREAMOS LA TABLA DE AUDITORÍA (El "Libro Mayor" del stock)
        Schema::create('movimientos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users'); // Quién hizo el movimiento
            
            // Tipo: 'Ingreso OC', 'Venta', 'Ajuste Manual'
            $table->string('tipo_movimiento'); 
            
            // La historia clínica de los números
            $table->decimal('cantidad_anterior', 10, 3);
            $table->decimal('cantidad_movimiento', 10, 3); // Puede ser positivo (+10) o negativo (-2)
            $table->decimal('cantidad_actual', 10, 3);
            
            // El porqué
            $table->string('motivo')->nullable(); // Ej: "Se rompió el pack", "Llegó proveedor"
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_stock');
        
        Schema::table('producto_sucursal', function (Blueprint $table) {
            $table->integer('cantidad_fisica')->default(0)->change();
            $table->integer('cantidad_reservada')->default(0)->change();
        });
    }
};