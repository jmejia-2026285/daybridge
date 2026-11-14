<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] #[Title('Verifica tu correo · Daybridge')] class extends Component
{
    public function sendVerification(): void
    {
        if (Auth::user()->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('home', absolute: false), navigate: true);

            return;
        }

        Auth::user()->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }
}; ?>

<div>
    <header class="mb-6">
        <p class="text-2xs font-semibold tracking-label text-ink-muted uppercase">Un último paso</p>
        <h1 class="mt-1 font-display text-2xl font-bold tracking-tight">Verifica tu correo</h1>
        <p class="mt-1 text-sm text-ink-muted">
            Te enviamos un enlace de verificación. Si no lo recibiste, podemos enviarte otro.
        </p>
    </header>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 border border-line bg-success-soft px-4 py-3 text-sm font-medium text-success">
            Enviamos un nuevo enlace a tu correo.
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3">
        <x-primary-button wire:click="sendVerification">
            Reenviar correo
        </x-primary-button>

        <button wire:click="logout" type="submit" class="text-sm text-ink-muted transition duration-fast ease-signal hover:text-accent-strong">
            Cerrar sesión
        </button>
    </div>
</div>
