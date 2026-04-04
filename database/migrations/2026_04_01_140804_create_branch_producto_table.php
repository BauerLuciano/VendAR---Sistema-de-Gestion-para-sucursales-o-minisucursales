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
        // ACÁ LE METEMOS EL ESCUDO QUE TE DECÍA
        if (!Schema::hasTable('branch_producto')) {
            Schema::create('branch_producto', function (Blueprint $table) {
                $table->id();
                $table->foreignId('branch_id')->constrained('sucursales')->cascadeOnDelete();
                $table->foreignId('producto_id')->constrained('productos')->cascadeOnDelete();
                
                // CAMBIAMOS LOS NOMBRES DE ESTAS COLUMNAS:
                $table->integer('cantidad_fisica')->default(0);
                $table->integer('cantidad_reservada')->default(0);
                
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branch_producto');
    }
};