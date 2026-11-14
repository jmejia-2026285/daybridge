<?php

use App\Models\User;
use App\Support\ActionThrottle;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] #[Title('Crear cuenta · Daybridge')] class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function register(): void
    {
        ActionThrottle::hit('register', 5, 600, 'email');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = new User([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'joined_at' => now()->toDateString(),
        ]);

        // Las cuentas creadas por registro público quedan pendientes hasta que
        // un administrador las active en Admin › Personas.
        $user->forceFill(['is_active' => false])->save();

        event(new Registered($user));

        session()->flash('status', 'Tu cuenta fue creada. Un administrador debe activarla antes de que puedas iniciar sesión.');

        $this->redirect(route('login', absolute: false), navigate: true);
    }
}; ?>

<div>
    <header class="mb-6">
        <p class="text-2xs font-semibold tracking-label text-ink-muted uppercase">Primera vez</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-tight">Crea tu cuenta</h1>
        <p class="mt-1 text-sm text-ink-muted">Únete al espacio de comunicación de tu equipo.</p>
    </header>

    <form wire:submit="register" class="space-y-4">
        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input wire:model="name" id="name" class="mt-1.5" type="text" name="name" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input wire:model="email" id="email" class="mt-1.5" type="email" name="email" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input wire:model="password" id="password" class="mt-1.5" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmar contraseña" />
            <x-text-input wire:model="password_confirmation" id="password_confirmation" class="mt-1.5" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <a href="{{ route('login') }}" wire:navigate class="text-sm text-ink-muted transition duration-fast ease-signal hover:text-accent-strong">
                ¿Ya tienes cuenta?
            </a>

            <x-primary-button>
                Registrarme
            </x-primary-button>
        </div>
    </form>
</div>
