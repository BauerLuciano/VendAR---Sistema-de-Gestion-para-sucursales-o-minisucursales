<?php

namespace Database\Seeders;

use App\Models\Consumidor;
use Illuminate\Database\Seeder;

class ConsumidorSeeder extends Seeder
{
    public function run(): void
    {
        Consumidor::updateOrCreate(
            ['id' => 1],
            [
                'nombre' => 'Consumidor Final',
                'dni' => '00000000',
                'telefono' => null,
                'direccion' => null,
                'limite_cuenta_corriente' => 0,
            ]
        );
    }
}