<?php

namespace Database\Factories;

use App\Models\Equipo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Equipo> */
class EquipoFactory extends Factory
{
    protected $model = Equipo::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('EQ-####-????'),
            'nombre' => 'Proyector',
            'categoria' => 'Audiovisual',
            'estado' => Equipo::DISPONIBLE,
        ];
    }
}
