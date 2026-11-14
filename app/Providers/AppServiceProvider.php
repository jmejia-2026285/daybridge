<?php

namespace App\Providers;

use App\Http\Middleware\EnsureUserIsActive;
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
        // Las peticiones /livewire/update solo re-aplican el middleware
        // "persistente": sin esto, un usuario desactivado con la página abierta
        // podía seguir ejecutando acciones.
        Livewire::addPersistentMiddleware([
            EnsureUserIsActive::class,
        ]);
    }
}
