<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Semilla de roles por defecto
     */
    public function run(): void
    {
        Role::firstOrCreate([
        'name' => 'admin',
        'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'sub_admin',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'moderator',
            'guard_name' => 'web',
        ]);

        Role::firstOrCreate([
            'name' => 'collaborator',
            'guard_name' => 'web',
        ]);
    }
}
