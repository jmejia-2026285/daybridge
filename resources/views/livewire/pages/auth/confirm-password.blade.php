<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] #[Title('Confirmar contraseña · Daybridge')] class extends Component
{
    public string $password = '';

    public function confirmPassword(): void
    {
        $this->validate([
            'password' => ['required', 'string'],
        ]);

        if (! Auth::guard('web')->validate([
            'email' => Auth::user()->email,
            'password' => $this->password,
        ])) {
            throw ValidationException::withMessages([
                'password' => __('auth.password'),
            ]);
        }

        session(['auth.password_confirmed_at' => time()]);

        $this->redirectIntended(default: route('home', absolute: false), navigate: true);
    }
}; ?>

<div>
    <header class="mb-6">
        <p class="text-2xs font-semibold tracking-label text-ink-muted uppercase">Área segura</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-tight">Confirma tu contraseña</h1>
        <p class="mt-1 text-sm text-ink-muted">Necesitamos verificarte antes de continuar.</p>
    </header>

    <form wire:submit="confirmPassword" class="space-y-4">
        <div>
            <x-input-label for="password" value="Contraseña" />
            <x-text-input wire:model="password" id="password" class="mt-1.5" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" />
        </div>

        <div class="flex justify-end pt-1">
            <x-primary-button>
                Confirmar
            </x-primary-button>
        </div>
    </form>
</div>
