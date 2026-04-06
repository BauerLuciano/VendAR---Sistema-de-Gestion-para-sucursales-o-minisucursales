<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'luciano@gmail.com'], // Busca por este email
            [
                'name' => 'Luciano', // Ojo: si tu columna se llama 'nombre' en vez de 'name', cambialo acá
                'password' => Hash::make('123456'),
                'sucursal_id' => 1, // Descomentá esto si tu usuario necesita estar atado a una sucursal!
            ]
        );
    }
}