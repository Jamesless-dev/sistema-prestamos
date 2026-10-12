<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EncargadoSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'encargado@tecsup.edu.pe',
            'password' => Hash::make('Admin123'),
            'role' => 'encargado',
        ]);
    }
}