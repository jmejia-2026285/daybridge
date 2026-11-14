<?php

namespace App\Support;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Límite de frecuencia para acciones de Livewire (las peticiones a
 * /livewire/update no pasan por el middleware throttle de las rutas).
 */
final class ActionThrottle
{
    /**
     * @throws ValidationException cuando se excede el límite.
     */
    public static function hit(string $action, int $maxAttempts, int $decaySeconds = 60, string $errorField = 'throttle'): void
    {
        $key = $action.':'.(auth()->id() ?? request()->ip());

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                $errorField => "Demasiadas acciones seguidas. Intenta de nuevo en {$seconds} s.",
            ]);
        }

        RateLimiter::hit($key, $decaySeconds);
    }
}
