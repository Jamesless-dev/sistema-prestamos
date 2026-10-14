<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EquipoTest extends TestCase
{
    use RefreshDatabase;

    public function test_encargado_can_view_empty_list_and_forms(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']));
        $this->get(route('equipos.index'))->assertOk()->assertSee('No hay equipos registrados.');
        $this->get(route('equipos.create'))->assertOk()->assertSee('Registrar equipo');

        $equipo = Equipo::factory()->create();
        $this->get(route('equipos.edit', $equipo))->assertOk()->assertSee($equipo->codigo);
    }

    public function test_list_shows_equipment_and_escapes_user_input(): void
    {
        $equipo = Equipo::factory()->create(['nombre' => '<script>alert(1)</script>']);
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->get(route('equipos.index'))->assertOk()
            ->assertSee($equipo->codigo)->assertSee('Disponible')
            ->assertSee($equipo->nombre)->assertDontSee($equipo->nombre, false);
    }

    #[DataProvider('estados')]
    public function test_encargado_can_register_allowed_states(string $estado): void
    {
        $datos = Equipo::factory()->raw(['estado' => $estado]);
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->post(route('equipos.store'), $datos)
            ->assertRedirect(route('equipos.index'))->assertSessionHas('success');

        $this->assertDatabaseHas('equipos', $datos);
    }

    public static function estados(): array
    {
        return [['disponible']];
    }

    public function test_encargado_can_update_without_changing_unique_code_and_change_state(): void
    {
        $equipo = Equipo::factory()->create();
        $datos = [
            'codigo' => $equipo->codigo,
            'nombre' => 'Laptop actualizada',
            'categoria' => 'Computación',
            'estado' => 'prestado',
        ];
        $prestamo = $equipo->prestamos()->create(['estudiante_id' => User::factory()->create()->id]);
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->put(route('equipos.update', $equipo), $datos)
            ->assertRedirect(route('equipos.index'))->assertSessionHas('success');
        $this->assertDatabaseHas('equipos', ['id' => $equipo->id] + $datos);
        $this->assertFalse($equipo->fresh()->estaDisponible());

        $datos['estado'] = 'disponible';
        $this->put(route('equipos.update', $equipo), $datos)->assertSessionHasErrors('estado');
        $this->assertSame('prestado', $equipo->fresh()->estado);
        $prestamo->update(['fecha_devolucion' => now()]);
        $this->put(route('equipos.update', $equipo), $datos)->assertRedirect(route('equipos.index'));
        $this->assertTrue($equipo->fresh()->estaDisponible());
    }

    public function test_required_fields_are_rejected_without_inserting(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->post(route('equipos.store'), [])
            ->assertSessionHasErrors(['codigo', 'nombre', 'categoria', 'estado']);
        $this->assertDatabaseCount('equipos', 0);
    }

    public function test_duplicate_codes_are_rejected_on_create_and_update(): void
    {
        $existente = Equipo::factory()->create();
        $equipo = Equipo::factory()->create();
        $datos = Equipo::factory()->raw(['codigo' => $existente->codigo]);
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->post(route('equipos.store'), $datos)
            ->assertSessionHasErrors(['codigo' => 'Este código ya está registrado.']);
        $this->put(route('equipos.update', $equipo), $datos)
            ->assertSessionHasErrors(['codigo' => 'Este código ya está registrado.']);
        $this->assertDatabaseCount('equipos', 2);
        $this->assertSame($equipo->codigo, $equipo->fresh()->codigo);
    }

    #[DataProvider('datosInvalidos')]
    public function test_invalid_fields_are_rejected_on_create_and_update(string $campo, mixed $valor): void
    {
        $equipo = Equipo::factory()->create();
        $datos = Equipo::factory()->raw([$campo => $valor]);
        $original = $equipo->only(['codigo', 'nombre', 'categoria', 'estado']);
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->post(route('equipos.store'), $datos)->assertSessionHasErrors($campo);
        $this->put(route('equipos.update', $equipo), $datos)->assertSessionHasErrors($campo);
        $this->assertDatabaseCount('equipos', 1);
        $this->assertDatabaseHas('equipos', ['id' => $equipo->id] + $original);
    }

    public static function datosInvalidos(): array
    {
        return [
            'estado desconocido' => ['estado', 'dañado'],
            'estado vacío' => ['estado', ''],
            'código vacío' => ['codigo', ''],
            'nombre vacío' => ['nombre', ''],
            'categoría vacía' => ['categoria', ''],
            'código largo' => ['codigo', str_repeat('x', 256)],
            'nombre largo' => ['nombre', str_repeat('x', 256)],
            'categoría larga' => ['categoria', str_repeat('x', 256)],
            'código no textual' => ['codigo', ['x']],
            'nombre no textual' => ['nombre', ['x']],
            'categoría no textual' => ['categoria', ['x']],
        ];
    }

    public function test_encargado_can_delete_equipment_without_loans(): void
    {
        $equipo = Equipo::factory()->create();
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->delete(route('equipos.destroy', $equipo))
            ->assertRedirect(route('equipos.index'))->assertSessionHas('success');
        $this->assertDatabaseMissing('equipos', ['id' => $equipo->id]);
    }

    public function test_deletion_preserves_equipment_with_loan_history(): void
    {
        $equipo = Equipo::factory()->create();
        $estudiante = User::factory()->create(['role' => 'estudiante']);
        $prestamo = $equipo->prestamos()->create([
            'estudiante_id' => $estudiante->id,
            'fecha_devolucion' => now(),
        ]);
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->delete(route('equipos.destroy', $equipo))
            ->assertRedirect(route('equipos.index'))->assertSessionHas('error');
        $this->assertDatabaseHas('equipos', ['id' => $equipo->id]);
        $this->assertDatabaseHas('prestamos', ['id' => $prestamo->id]);
    }

    #[DataProvider('rutas')]
    public function test_students_cannot_access_equipment_routes(string $metodo, string $ruta): void
    {
        $equipo = Equipo::factory()->create();
        $url = str_replace('{id}', (string) $equipo->id, $ruta);
        $this->actingAs(User::factory()->create(['role' => 'estudiante']))
            ->call($metodo, $url, Equipo::factory()->raw())->assertForbidden();
        $this->assertDatabaseCount('equipos', 1);
        $this->assertDatabaseHas('equipos', ['id' => $equipo->id, 'codigo' => $equipo->codigo]);
    }

    #[DataProvider('rutas')]
    public function test_guests_are_redirected_to_login(string $metodo, string $ruta): void
    {
        $equipo = Equipo::factory()->create();
        $this->call($metodo, str_replace('{id}', (string) $equipo->id, $ruta))
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('equipos', 1);
    }

    public static function rutas(): array
    {
        return [
            ['GET', '/equipos'],
            ['GET', '/equipos/create'],
            ['POST', '/equipos'],
            ['GET', '/equipos/{id}/edit'],
            ['PUT', '/equipos/{id}'],
            ['PATCH', '/equipos/{id}'],
            ['DELETE', '/equipos/{id}'],
        ];
    }

    public function test_unknown_equipment_returns_not_found(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']));
        $this->get('/equipos/99999/edit')->assertNotFound();
        $this->put('/equipos/99999', Equipo::factory()->raw())->assertNotFound();
        $this->delete('/equipos/99999')->assertNotFound();
    }

    public function test_new_equipment_cannot_be_created_as_borrowed_or_reserved(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']));
        foreach (['prestado', 'reservado'] as $estado) {
            $this->post(route('equipos.store'), Equipo::factory()->raw(['estado' => $estado]))->assertSessionHasErrors('estado');
        }
        $this->assertDatabaseCount('equipos', 0);
    }

    public function test_equipment_with_reservation_history_cannot_be_deleted(): void
    {
        $equipo = Equipo::factory()->create();
        $reserva = $equipo->reservas()->create([
            'estudiante_id' => User::factory()->create()->id,
            'fecha_inicio' => now()->addDay(), 'fecha_fin' => now()->addDay()->addHour(),
        ]);
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->delete(route('equipos.destroy', $equipo))->assertSessionHas('error');
        $this->assertModelExists($equipo);
        $this->assertModelExists($reserva);
    }
}
