<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Consumidor;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        // Creamos la sucursal principal
        Branch::firstOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Casa Central',
                'direccion' => 'Av. Principal 123',
                'telefono' => '123456789',
                'estado' => true
            ]
        );

        Consumidor::firstOrCreate(
            ['id' => 1],
            ['nombre' => 'Consumidor Final']
        );
    }
}