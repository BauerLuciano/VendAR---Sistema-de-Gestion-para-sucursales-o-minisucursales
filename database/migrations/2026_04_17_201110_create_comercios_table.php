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
    Schema::create('comercios', function (Blueprint $table) {
        $table->id();
        $table->string('nombre'); // Ej: "Kiosco El Trébol"
        $table->string('slug')->unique(); // Para URLs: vendAR.com/kiosco-el-trebol
        
        // Control de Estado
        $table->enum('status', ['activo', 'suspendido', 'trial'])->default('trial');
        
        // Lógica de Negocio (SaaS)
        $table->string('plan')->default('basico'); // basico, pro, premium
        $table->json('modulos_habilitados')->nullable(); // Aquí guardamos: {"pos": true, "cuentas": false, "stock": true}
        
        $table->integer('limite_sucursales')->default(1);
        $table->date('vencimiento_pago')->nullable(); // Para saber cuándo cortarle el servicio
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comercios');
    }
};
