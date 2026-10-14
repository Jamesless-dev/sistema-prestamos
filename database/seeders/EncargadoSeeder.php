<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EncargadoSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'encargado@tecsup.edu.pe'], [
            'name' => 'Administrador',
            'password' => Hash::make('Admin123'),
            'role' => 'encargado',
            'email_verified_at' => now(),
        ]);
    }
}
