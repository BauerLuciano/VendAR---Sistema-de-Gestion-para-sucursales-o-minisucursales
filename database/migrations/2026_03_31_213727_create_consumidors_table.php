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
        Schema::create('consumidores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido'); // Añadido de doc
            $table->string('documento')->unique()->nullable(); // Renombrado de dni a documento
            $table->string('email')->unique()->nullable(); // Añadido de doc
            $table->string('telefono')->nullable();
            $table->string('direccion')->nullable();
            $table->decimal('limite_cuenta_corriente', 10, 2)->default(0);
            $table->boolean('estado')->default(true); // Añadido de doc (Activo/Inactivo)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('consumidores');
    }
};