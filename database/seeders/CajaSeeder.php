<?php

namespace Database\Seeders;

use App\Models\Caja;
use App\Models\Sucursal;
use Illuminate\Database\Seeder;

class CajaSeeder extends Seeder
{
    public function run(): void
    {
        // Buscamos la primera sucursal que exista en tu BD
        $sucursal = Sucursal::first(); 

        if ($sucursal) {
            Caja::updateOrCreate(
                ['nombre' => 'Caja Principal', 'sucursal_id' => $sucursal->id],
                ['estado' => true]
            );

            Caja::updateOrCreate(
                ['nombre' => 'Caja Kiosco Ventana', 'sucursal_id' => $sucursal->id],
                ['estado' => true]
            );
        }
    }
}