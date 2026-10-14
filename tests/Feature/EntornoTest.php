<?php

namespace Tests\Feature;

use App\Services\ControladoresPdo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class EntornoTest extends TestCase
{
    public function test_current_database_driver_is_reported_without_connecting(): void
    {
        $extension = 'pdo_'.DB::connection()->getDriverName();
        $this->artisan('entorno:verificar')
            ->expectsOutputToContain('PHP utilizado:')
            ->expectsOutputToContain('Controlador '.$extension.' disponible.')
            ->assertSuccessful();
    }

    public static function missingDrivers(): array
    {
        return [
            'postgresql' => ['pgsql', 'pdo_pgsql'],
            'mysql' => ['mysql', 'pdo_mysql'],
            'sqlite' => ['sqlite', 'pdo_sqlite'],
        ];
    }

    #[DataProvider('missingDrivers')]
    public function test_missing_driver_is_explained_and_stops_setup(string $driver, string $extension): void
    {
        config(['database.default' => $driver]);
        $this->mock(ControladoresPdo::class, function (MockInterface $mock) {
            $mock->shouldReceive('disponibles')->once()->andReturn([]);
        });

        $this->artisan('entorno:verificar')
            ->expectsOutputToContain('Falta el controlador '.$extension)
            ->expectsOutputToContain('Activa extension='.$extension)
            ->assertFailed();
    }

    public function test_database_connection_can_be_checked(): void
    {
        $this->artisan('entorno:verificar', ['--conexion' => true])
            ->expectsOutputToContain('Conexión a la base de datos correcta.')
            ->assertSuccessful();
    }

    public function test_local_missing_driver_page_explains_the_postgresql_fix(): void
    {
        $this->app['env'] = 'local';
        Route::get('/test-driver', fn () => throw new QueryException('pgsql', 'select 1', [], new PDOException('could not find driver')));

        $this->get('/test-driver')->assertStatus(503)
            ->assertSee('pdo_pgsql')->assertSee('configurar-postgresql.ps1')
            ->assertDontSee('Illuminate\\Database\\QueryException');
    }

    public function test_production_missing_driver_page_hides_local_repair_details(): void
    {
        $this->app['env'] = 'production';
        Route::get('/test-driver', fn () => throw new QueryException('pgsql', 'select 1', [], new PDOException('could not find driver')));

        $this->get('/test-driver')->assertStatus(503)
            ->assertSee('Contacta al encargado')->assertDontSee('php.ini')->assertDontSee('configurar-postgresql.ps1');
    }

    public function test_missing_driver_has_a_controlled_json_response(): void
    {
        Route::get('/test-driver', fn () => throw new QueryException('pgsql', 'select 1', [], new PDOException('could not find driver')));

        $this->getJson('/test-driver')->assertStatus(503)
            ->assertExactJson(['message' => 'La conexión de base de datos no está disponible: falta su controlador PHP.']);
    }
}
