<?php

namespace Database\Seeders;

use App\Models\Equipo;
use Illuminate\Database\Seeder;

class EquipoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $equipos = [
            ['EQ-001', 'Laptop Lenovo ThinkPad', 'Computación'],
            ['EQ-002', 'Laptop HP ProBook', 'Computación'],
            ['EQ-003', 'Proyector Epson', 'Audiovisual'],
            ['EQ-004', 'Cámara Canon', 'Audiovisual'],
            ['EQ-005', 'Multímetro digital', 'Electrónica'],
            ['EQ-006', 'Osciloscopio', 'Electrónica'],
            ['EQ-007', 'Router Cisco', 'Redes'],
            ['EQ-008', 'Switch de laboratorio', 'Redes'],
        ];

        foreach ($equipos as [$codigo, $nombre, $categoria]) {
            Equipo::firstOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'categoria' => $categoria,
                'estado' => Equipo::DISPONIBLE,
            ]);
        }
    }
}
