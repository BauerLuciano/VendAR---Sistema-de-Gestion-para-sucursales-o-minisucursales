<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            // 1. Agregamos el user_id (que es el que te tiró error en el POS)
            if (!Schema::hasColumn('ventas', 'user_id')) {
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            }
            
            // 2. Agregamos la fecha
            if (!Schema::hasColumn('ventas', 'fecha')) {
                $table->timestamp('fecha')->useCurrent();
            }
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            
            $table->dropForeign(['branch_id']);
            $table->dropColumn('branch_id');
            
            $table->dropColumn('fecha');
        });
    }
};