<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // Solo agrega 'estado' si no existe
            if (!Schema::hasColumn('ventas', 'estado')) {
                $table->string('estado')->default('activa');
            }
            
            // Solo agrega 'motivo_anulacion' si no existe
            if (!Schema::hasColumn('ventas', 'motivo_anulacion')) {
                $table->string('motivo_anulacion')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn(['estado', 'motivo_anulacion']);
        });
    }
};