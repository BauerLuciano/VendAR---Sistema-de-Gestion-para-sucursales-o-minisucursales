<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('producto_sucursal', function (Blueprint $table) {
            $table->decimal('cantidad_fisica', 10, 3)->change();
            $table->decimal('cantidad_reservada', 10, 3)->change();
        });
    }

    public function down()
    {
        Schema::table('producto_sucursal', function (Blueprint $table) {
            $table->integer('cantidad_fisica')->change();
            $table->integer('cantidad_reservada')->change();
        });
    }
};