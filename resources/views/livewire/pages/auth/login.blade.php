<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] #[Title('Entrar · Daybridge')] class extends Component
{
    public LoginForm $form;

    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('home', absolute: false), navigate: true);
    }
}; ?>

<div>
    <header class="mb-6">
        <p class="text-2xs font-semibold tracking-label text-ink-muted uppercase">Bienvenido de nuevo</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-tight">Entra a Daybridge</h1>
        <p class="mt-1 text-sm text-ink-muted">La comunicación de tu equipo, en un solo lugar.</p>
    </header>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input wire:model="form.email" id="email" class="mt-1.5" type="email" name="email" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('form.email')" />
        </div>

        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input wire:model="form.password" id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('form.password')" />
        </div>

        <label for="remember" class="flex items-center gap-2.5 text-sm text-ink-secondary">
            <input wire:model="form.remember" id="remember" type="checkbox" class="h-4 w-4 rounded-xs border-line bg-canvas text-accent focus:ring-accent" name="remember">
            Recordarme
        </label>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            @if (Route::has('password.request'))
                <a class="text-sm text-ink-muted transition duration-fast ease-signal hover:text-accent-strong" href="{{ route('password.request') }}" wire:navigate>
                    ¿Olvidaste tu contraseña?
                </a>
            @endif

            <x-primary-button>
                Entrar
            </x-primary-button>
        </div>
    </form>

    @if (Route::has('register'))
        <p class="mt-6 border-t border-line pt-4 text-center text-sm text-ink-muted">
            ¿No tienes cuenta?
            <a href="{{ route('register') }}" wire:navigate class="font-medium text-accent-strong hover:underline">
                Crear una
            </a>
        </p>
    @endif
</div>
