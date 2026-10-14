<?php

namespace Tests\Feature;

use App\Models\Equipo;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SeguridadFormulariosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(PreventRequestForgery::class, function ($app) {
            return new class($app, $app['encrypter']) extends PreventRequestForgery
            {
                protected function runningUnitTests(): bool
                {
                    return false;
                }
            };
        });
    }

    public static function invalidTokens(): array
    {
        return ['sin token' => [null], 'token incorrecto' => ['token-de-otra-sesion']];
    }

    #[DataProvider('invalidTokens')]
    public function test_equipment_form_rejects_cross_site_requests_without_saving_data(?string $token): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->withSession(['_token' => 'token-de-la-sesion']);

        $this->post(route('equipos.store'), [
            '_token' => $token, 'codigo' => 'QA-CSRF', 'nombre' => 'Laptop QA',
            'categoria' => 'Computación', 'estado' => 'disponible',
        ])->assertStatus(419);

        $this->assertDatabaseCount('equipos', 0);
    }

    public function test_equipment_form_accepts_its_session_token_and_ignores_unexpected_fields(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'encargado']))
            ->withSession(['_token' => 'token-de-la-sesion']);

        $this->post(route('equipos.store'), [
            '_token' => 'token-de-la-sesion', 'codigo' => 'QA-CSRF', 'nombre' => 'Laptop QA',
            'categoria' => 'Computación', 'estado' => 'disponible', 'id' => 999999,
        ])->assertRedirect(route('equipos.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseCount('equipos', 1);
        $this->assertDatabaseHas('equipos', ['codigo' => 'QA-CSRF', 'estado' => 'disponible']);
        $this->assertNotSame(999999, Equipo::firstOrFail()->id);
    }
}
