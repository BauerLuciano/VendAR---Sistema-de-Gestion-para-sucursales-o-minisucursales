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
        $table->decimal('saldo_final_efectivo_real', 12, 2)->nullable();
        $table->decimal('saldo_final_mp_real', 12, 2)->nullable();
        $table->decimal('saldo_final_transf_real', 12, 2)->nullable();
        $table->text('observaciones_cierre')->nullable();
        $table->foreignId('user_cierre_id')->nullable()->constrained('users');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('turno_cajas', function (Blueprint $table) {
            //
        });
    }
};
