<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Consumidor;
use App\Models\User;
use Illuminate\Database\Seeder;

class FixDatosMaestrosSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear al menos una sucursal (ID: 1)
        $branch = Branch::firstOrCreate(
            ['id' => 1],
            ['name' => 'Casa Central', 'address' => 'Dirección de Prueba 123'] 
        );

        // 2. Asignar branch_id = 1 al usuario de prueba (Vos)
        $user = User::find(1);
        if ($user) {
            $user->update(['branch_id' => $branch->id]);
        }

        // 3. Crear el "Consumidor Final" (ID: 1)
        Consumidor::firstOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Consumidor Final', 
                // Agregá 'estado' => true si tu tabla lo requiere
            ]
        );
    }
}