<?php

namespace App\Providers;

use App\Http\Middleware\EnsureEmailVerificationEnabled;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Livewire::addPersistentMiddleware([
            RoleMiddleware::class,
            EnsureEmailVerificationEnabled::class,
        ]);
    }
}
