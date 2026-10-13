<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RolesTest extends TestCase
{
    use RefreshDatabase;

    public static function roles(): array
    {
        return [['encargado', 'dashboard.encargado'], ['estudiante', 'dashboard.estudiante']];
    }

    #[DataProvider('roles')]
    public function test_login_sends_each_role_to_its_panel(string $role, string $panel): void
    {
        $user = User::factory()->create(['role' => $role]);
        $this->withSession(['url.intended' => '/equipos/create']);

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login')
            ->assertHasNoErrors()
            ->assertRedirect(route($panel, absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertFalse(session()->has('url.intended'));
    }

    #[DataProvider('roles')]
    public function test_common_entries_redirect_by_role(string $role, string $panel): void
    {
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->get('/')->assertRedirect(route($panel));
        $this->get('/dashboard')->assertRedirect(route($panel));
    }

    public function test_students_only_see_available_equipment(): void
    {
        $available = Equipo::factory()->create(['estado' => 'disponible']);
        $borrowed = Equipo::factory()->create(['estado' => 'prestado']);

        $this->actingAs(User::factory()->create(['role' => 'estudiante']))
            ->get(route('dashboard.estudiante'))
            ->assertOk()
            ->assertSee($available->codigo)
            ->assertDontSee($borrowed->codigo)
            ->assertDontSee('href="'.route('equipos.create').'"', false);
    }

    public function test_each_role_is_denied_the_other_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'estudiante']))
            ->get(route('dashboard.encargado'))->assertForbidden();
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->get(route('dashboard.estudiante'))->assertForbidden();
    }

    public static function equipmentRoutes(): array
    {
        return [
            ['GET', '/equipos'], ['GET', '/equipos/create'],
            ['POST', '/equipos'], ['GET', '/equipos/{id}/edit'],
            ['PUT', '/equipos/{id}'], ['PATCH', '/equipos/{id}'],
            ['DELETE', '/equipos/{id}'],
        ];
    }

    #[DataProvider('equipmentRoutes')]
    public function test_students_cannot_open_or_modify_equipment(string $method, string $path): void
    {
        $equipment = Equipo::factory()->create();
        $original = $equipment->only(['codigo', 'nombre', 'categoria', 'estado']);
        $this->actingAs(User::factory()->create(['role' => 'estudiante']))
            ->call($method, str_replace('{id}', (string) $equipment->id, $path), [])
            ->assertForbidden();
        $this->assertDatabaseCount('equipos', 1);
        $this->assertDatabaseHas('equipos', ['id' => $equipment->id] + $original);
    }

    public function test_guests_must_login_to_open_internal_routes(): void
    {
        foreach (['/dashboard', '/dashboard/encargado', '/dashboard/estudiante', '/equipos/create'] as $path) {
            $this->get($path)->assertRedirect(route('login'));
        }
    }

    public function test_unknown_role_has_no_panel(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'otro']));
        $this->get('/dashboard')->assertForbidden();
        $this->get('/dashboard/encargado')->assertForbidden();
        $this->get('/dashboard/estudiante')->assertForbidden();
    }
}
