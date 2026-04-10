<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Eliminamos la restricción CHECK de PostgreSQL
        DB::statement('ALTER TABLE orden_compras DROP CONSTRAINT IF EXISTS orden_compras_estado_check;');
    }

    public function down(): void
    {
        // Si queremos volver atrás, le volvemos a poner la restricción vieja (ejemplo)
        DB::statement("ALTER TABLE orden_compras ADD CONSTRAINT orden_compras_estado_check CHECK (estado::text = ANY (ARRAY['Borrador'::character varying, 'Pendiente'::character varying, 'Completada'::character varying, 'Cancelada'::character varying]::text[]))");
    }
};