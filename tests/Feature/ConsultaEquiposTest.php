<?php

namespace Tests\Feature;

use App\Livewire\ConsultaEquipos;
use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ConsultaEquiposTest extends TestCase
{
    use RefreshDatabase;

    public static function searches(): array
    {
        return [
            'nombre en minúsculas' => ['laptop'],
            'nombre en mayúsculas' => ['LAPTOP'],
            'código en minúsculas' => ['eq-qa'],
            'categoría en minúsculas' => ['electrónica'],
        ];
    }

    #[DataProvider('searches')]
    public function test_search_matches_available_equipment_regardless_of_case(string $search): void
    {
        $available = Equipo::factory()->create(['codigo' => 'EQ-QA', 'nombre' => 'Laptop QA', 'categoria' => 'Electrónica']);
        $borrowed = Equipo::factory()->create(['nombre' => 'Laptop prestada', 'categoria' => 'Electrónica', 'estado' => 'prestado']);
        $reserved = Equipo::factory()->create(['nombre' => 'Laptop reservada', 'categoria' => 'Electrónica', 'estado' => 'reservado']);
        $other = Equipo::factory()->create(['nombre' => 'Proyector', 'categoria' => 'Audiovisual']);
        $this->actingAs(User::factory()->create());

        Livewire::test(ConsultaEquipos::class)->set('busqueda', $search)
            ->assertSee($available->codigo)->assertDontSee($borrowed->codigo)
            ->assertDontSee($reserved->codigo)->assertDontSee($other->codigo);
    }

    public function test_search_can_show_no_results_and_recover_after_clearing_the_filter(): void
    {
        $equipo = Equipo::factory()->create(['nombre' => 'Laptop QA']);
        $this->actingAs(User::factory()->create());

        Livewire::test(ConsultaEquipos::class)->set('busqueda', 'sin coincidencias')
            ->assertSee('No hay equipos disponibles')->assertDontSee($equipo->codigo)
            ->set('busqueda', '')->assertSee($equipo->codigo);
    }
}
