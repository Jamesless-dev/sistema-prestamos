<?php

namespace Database\Seeders;

use App\Models\Equipo;
use Illuminate\Database\Seeder;

class EquipoSeeder extends Seeder
{
    public function run(): void
    {
        $equipos = [
            ['codigo' => 'EQ-001', 'nombre' => 'Laptop Dell Latitude', 'categoria' => 'Computación', 'estado' => 'disponible'],
            ['codigo' => 'EQ-002', 'nombre' => 'Proyector Epson PowerLite', 'categoria' => 'Audiovisual', 'estado' => 'disponible'],
            ['codigo' => 'EQ-003', 'nombre' => 'Kit Arduino Uno R3', 'categoria' => 'Electrónica', 'estado' => 'disponible'],
            ['codigo' => 'EQ-004', 'nombre' => 'Multímetro Digital Fluke', 'categoria' => 'Medición', 'estado' => 'disponible'],
            ['codigo' => 'EQ-005', 'nombre' => 'Router Cisco 2900', 'categoria' => 'Redes', 'estado' => 'disponible'],
            ['codigo' => 'EQ-006', 'nombre' => 'Impresora 3D Creality', 'categoria' => 'Fabricación', 'estado' => 'disponible'],
            ['codigo' => 'EQ-007', 'nombre' => 'Osciloscopio Rigol', 'categoria' => 'Medición', 'estado' => 'disponible'],
            ['codigo' => 'EQ-008', 'nombre' => 'Tablet Samsung Galaxy Tab', 'categoria' => 'Computación', 'estado' => 'disponible'],
        ];

        foreach ($equipos as $equipo) {
            Equipo::create($equipo);
        }
    }
}