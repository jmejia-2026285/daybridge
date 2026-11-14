<?php

use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] #[Title('Recuperar contraseña · Daybridge')] class extends Component
{
    public string $email = '';

    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink(
            $this->only('email')
        );

        if ($status != Password::RESET_LINK_SENT) {
            $this->addError('email', __($status));

            return;
        }

        $this->reset('email');

        session()->flash('status', __($status));
    }
}; ?>

<div>
    <header class="mb-6">
        <p class="text-2xs font-semibold tracking-label text-ink-muted uppercase">Acceso</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-tight">Recupera tu contraseña</h1>
        <p class="mt-1 text-sm text-ink-muted">Te enviaremos un enlace para elegir una nueva.</p>
    </header>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="space-y-4">
        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input wire:model="email" id="email" class="mt-1.5" type="email" name="email" required autofocus />
            <x-input-error :messages="$errors->get('email')" />
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-1">
            <a href="{{ route('login') }}" wire:navigate class="text-sm text-ink-muted transition duration-fast ease-signal hover:text-accent-strong">
                Volver al acceso
            </a>

            <x-primary-button>
                Enviar enlace
            </x-primary-button>
        </div>
    </form>
</div>
