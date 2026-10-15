<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Juan',
            'email' => 'juan@gmail.com',
            'password' => Hash::make('facil'),
            'role' => 'estudiante',
        ]);

        User::create([
            'name' => 'Maria Lopez',
            'email' => 'maria@gmail.com',
            'password' => Hash::make('craken3214'),
            'role' => 'estudiante',
        ]);
    }
}