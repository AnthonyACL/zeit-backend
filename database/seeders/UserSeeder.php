<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $anthony = User::factory()->create([
            'name' => 'Anthony',
            'email' => 'test@example.com',
            'password' => '12345678', 
        ]);
        
        //Le asignamos el rol de 'user', como solicitaste.
        $anthony->assignRole('admin');

        $samir = User::factory()->create([
            'name' => 'Samir',
            'email' => 'samir@example.com',
            'password' => '12345678',
        ]);

        $samir->assignRole('sub_admin');

        $adriano = User::factory()->create([
            'name' => 'Adriano',
            'email' => 'adriano@example.com',
            'password' => '12345678',
        ]);

        $adriano->assignRole('moderator');

        $robin = User::factory()->create([
            'name' => 'Robin',
            'email' => 'robin@example.com',
            'password' => '12345678',
        ]); 

        $robin->assignRole('worker');
    }
}
