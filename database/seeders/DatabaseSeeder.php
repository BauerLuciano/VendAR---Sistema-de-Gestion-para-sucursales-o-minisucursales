<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash; // <- Importante agregar esto para tu contraseña

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Crear TU usuario para que nunca más te quedes afuera
        User::updateOrCreate(
            ['email' => 'luciano@gmail.com'], // Busca por tu mail
            [
                'name' => 'Luciano',
                'password' => Hash::make('123456'), // Contraseña encriptada
            ]
        );

        // 2. Mantenemos el usuario genérico por si tu colega lo usa para pruebas
        User::updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'), 
            ]
        );

        $this->call([
            ConsumidorSeeder::class, 
            CajaSeeder::class,     
            FixDatosMaestrosSeeder::class, 
        ]);
    }
}