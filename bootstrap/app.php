<?php

use App\Http\Middleware\RoleMiddleware;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (QueryException $exception, Request $request) {
            if (! str_contains($exception->getMessage(), 'could not find driver')) {
                return null;
            }

            if ($request->expectsJson()) {
                return response()->json(['message' => 'La conexión de base de datos no está disponible: falta su controlador PHP.'], 503);
            }

            $driver = config('database.connections.'.$exception->getConnectionName().'.driver', config('database.default'));
            $extension = match ($driver) {
                'pgsql' => 'pdo_pgsql',
                'mysql', 'mariadb' => 'pdo_mysql',
                'sqlite' => 'pdo_sqlite',
                default => 'PDO',
            };

            return response()->view('errors.database-driver', [
                'extension' => $extension,
                'mostrarDiagnostico' => app()->environment('local'),
            ], 503);
        });

        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
