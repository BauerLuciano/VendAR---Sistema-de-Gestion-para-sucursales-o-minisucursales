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
                'nombre' => 'Consumidor Final', // Acá está completo como querés
                'apellido' => '-', // Obligatorio por tu migración, le mandamos un guion
                'documento' => '00000000',
                'email' => null,
                'telefono' => null,
                'direccion' => null,
                'limite_cuenta_corriente' => 0,
            ]
        );
    }
}