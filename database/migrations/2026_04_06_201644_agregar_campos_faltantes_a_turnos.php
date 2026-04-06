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
    Schema::table('turno_cajas', function (Blueprint $table) {
    if (!Schema::hasColumn('turno_cajas', 'monto_apertura')) {
        $table->decimal('monto_apertura', 12, 2)->default(0);
    }
    if (!Schema::hasColumn('turno_cajas', 'sucursal_id')) {
        $table->unsignedBigInteger('sucursal_id')->nullable();
    }
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
