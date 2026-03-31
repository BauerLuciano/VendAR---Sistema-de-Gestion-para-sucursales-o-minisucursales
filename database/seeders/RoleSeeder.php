<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        Role::create(['name' => 'SuperAdmin']);
        Role::create(['name' => 'Dueño']);
        Role::create(['name' => 'Encargado']);
        Role::create(['name' => 'Cajero']);
    }
}