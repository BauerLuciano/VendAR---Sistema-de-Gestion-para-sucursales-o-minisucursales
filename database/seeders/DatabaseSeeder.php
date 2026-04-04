<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Si tenés seeders de Roles o Sucursales puros, llamalos acá primero.
        // $this->call([
        //     RoleSeeder::class,
        //     BranchSeeder::class,
        // ]);

        // 2. Crear el usuario administrador
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            // La password por defecto del factory suele ser 'password'
        ]);

        // 3. Ahora sí, corremos el Fix para atar los datos
        $this->call([
            FixDatosMaestrosSeeder::class,
        ]);
    }
}