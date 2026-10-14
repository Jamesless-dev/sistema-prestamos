<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class EstudianteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([1, 2] as $numero) {
            User::firstOrCreate(['email' => "estudiante{$numero}@tecsup.edu.pe"], [
                'name' => "Estudiante {$numero}",
                'password' => Hash::make('Estudiante123'),
                'role' => User::ROLE_ESTUDIANTE,
                'email_verified_at' => now(),
            ]);
        }
    }
}
