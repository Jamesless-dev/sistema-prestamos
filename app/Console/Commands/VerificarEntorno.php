<?php

namespace App\Console\Commands;

use App\Services\ControladoresPdo;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class VerificarEntorno extends Command
{
    protected $signature = 'entorno:verificar {--conexion : Comprueba también la conexión real a la base de datos}';

    protected $description = 'Comprueba el controlador PDO del PHP que ejecuta este proyecto';

    public function handle(ControladoresPdo $controladores): int
    {
        $this->line('PHP utilizado: '.PHP_BINARY);
        $this->line('php.ini utilizado: '.(php_ini_loaded_file() ?: 'No hay un php.ini cargado'));

        try {
            $conexion = DB::connection();
            $driver = $conexion->getDriverName();
        } catch (Throwable $e) {
            $this->error('La conexión configurada no es válida. Revisa DB_CONNECTION y DB_URL en .env.');

            return self::FAILURE;
        }

        $extension = match ($driver) {
            'pgsql' => 'pdo_pgsql',
            'mysql', 'mariadb' => 'pdo_mysql',
            'sqlite' => 'pdo_sqlite',
            'sqlsrv' => 'pdo_sqlsrv',
            default => 'pdo_'.$driver,
        };
        $pdoDriver = $driver === 'mariadb' ? 'mysql' : $driver;

        if (! in_array($pdoDriver, $controladores->disponibles(), true)) {
            $this->error('Falta el controlador '.$extension.' para la conexión '.$driver.'.');
            $this->line('Activa extension='.$extension.' en el php.ini indicado y reinicia php artisan serve.');

            if ($driver === 'pgsql' && PHP_OS_FAMILY === 'Windows') {
                $this->line('Puedes ejecutar:');
                $this->line('powershell -ExecutionPolicy Bypass -File .\configurar-postgresql.ps1 -PhpPath "'.PHP_BINARY.'"');
            }

            return self::FAILURE;
        }

        $this->info('Controlador '.$extension.' disponible.');

        if ($this->option('conexion')) {
            try {
                $conexion->getPdo();
            } catch (Throwable $e) {
                report($e);
                $this->error('El controlador está disponible, pero la conexión falló. Revisa que el servidor esté iniciado y que host, puerto, base, usuario y contraseña sean correctos.');

                return self::FAILURE;
            }

            $this->info('Conexión a la base de datos correcta.');
        }

        return self::SUCCESS;
    }
}
