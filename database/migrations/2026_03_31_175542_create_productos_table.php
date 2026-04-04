<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->foreignId('marca_id')->constrained('marcas')->restrictOnDelete();
            
            // Datos del producto (Alineado al Doc)
            $table->string('nombre');
            $table->string('codigo_barras', 50)->unique(); // Lenguaje Oblicuo
            $table->text('descripcion')->nullable();
            
            // Atributos de Análisis faltantes
            $table->enum('unidad_medida', ['Unidad', 'Kg', 'Gramos'])->default('Unidad');
            $table->boolean('es_retornable')->default(false);
            
            // Precios y Stock
            $table->decimal('precio_costo', 10, 2)->default(0);
            $table->decimal('precio_venta', 10, 2)->default(0);
            $table->integer('stock_minimo')->default(0);
            
            // Multimedia y Control
            $table->string('imagen')->nullable();
            $table->boolean('estado')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('productos');
    }
};