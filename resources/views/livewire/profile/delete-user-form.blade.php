<?php

use App\Enums\UserRole;
use App\Livewire\Actions\Logout;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Component;

new class extends Component
{
    public string $password = '';

    public function deleteUser(Logout $logout): void
    {
        $this->validate([
            'password' => ['required', 'string', 'current_password'],
        ]);

        $user = Auth::user();

        // Evita quedarse sin administradores.
        if ($user->isAdmin() && User::query()->where('role', UserRole::Admin)->where('is_active', true)->count() <= 1) {
            $this->addError('password', 'Eres el único administrador activo. Asigna otro antes de eliminar tu cuenta.');

            return;
        }

        tap($user, $logout(...))->delete();

        $this->redirect('/', navigate: true);
    }
}; ?>

<section>
    <header>
        <h2 class="font-display text-lg font-bold tracking-tight">Eliminar cuenta</h2>
        <p class="mt-1 max-w-prose text-sm text-ink-muted">
            Al eliminar tu cuenta, todos tus datos se borran de forma permanente. Descarga cualquier información que quieras conservar antes de continuar.
        </p>
    </header>

    <div class="mt-6">
        <x-danger-button
            x-data=""
            x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
        >Eliminar cuenta</x-danger-button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="deleteUser" class="p-6">
            <h2 class="font-display text-lg font-bold tracking-tight">
                ¿Seguro que quieres eliminar tu cuenta?
            </h2>

            <p class="mt-1 text-sm text-ink-muted">
                Esta acción es permanente. Escribe tu contraseña para confirmar.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Contraseña" class="sr-only" />

                <x-text-input
                    wire:model="password"
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1"
                    placeholder="Contraseña"
                />

                <x-input-error :messages="$errors->get('password')" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Cancelar
                </x-secondary-button>

                <x-danger-button>
                    Eliminar cuenta
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
