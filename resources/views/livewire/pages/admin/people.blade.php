<?php

use App\Enums\ItDeskRole;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Personas · Administración')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'rol')]
    public string $roleFilter = '';

    public string $status = '';

    public string $createName = '';

    public string $createEmail = '';

    public string $createPhone = '';

    public string $createJobTitle = '';

    public string $createAreaId = '';

    public string $createRole = '';

    public string $createPassword = '';

    public string $createPasswordConfirmation = '';

    public ?int $deletingUserId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', User::class), 403);
    }

    #[Computed]
    public function people(): Collection
    {
        return User::query()
            ->with('area')
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('job_title', 'like', "%{$this->search}%"),
            ))
            ->when($this->roleFilter !== '', fn (Builder $query): Builder => $query->where('role', $this->roleFilter))
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    #[Computed]
    public function areas(): Collection
    {
        return Area::query()->orderBy('sort_order')->get();
    }

    #[Computed]
    public function counts(): array
    {
        return [
            'total' => User::query()->count(),
            'admins' => User::query()->where('role', UserRole::Admin)->count(),
            'it_desk' => User::query()->whereNotNull('it_desk_role')->count(),
        ];
    }

    public function updateRole(int $userId, string $role): void
    {
        $person = User::query()->findOrFail($userId);

        abort_unless(auth()->user()->can('update', $person), 403);

        if ($person->is(auth()->user())) {
            $this->status = 'No puedes cambiar tu propio rol.';

            return;
        }

        $validated = validator(
            ['role' => $role],
            ['role' => ['required', Rule::enum(UserRole::class)]],
        )->validate();

        if ($person->isAdmin() && $validated['role'] !== UserRole::Admin->value && $this->counts['admins'] <= 1) {
            $this->status = 'Debe quedar al menos una persona con rol de administración.';

            return;
        }

        $person->update(['role' => UserRole::from($validated['role'])]);

        unset($this->people, $this->counts);

        $this->status = "Rol de {$person->name} actualizado.";
    }

    public function updateItDeskRole(int $userId, string $role): void
    {
        $person = User::query()->findOrFail($userId);

        abort_unless(auth()->user()->can('update', $person), 403);

        $validated = validator(
            ['it_desk_role' => $role === '' ? null : $role],
            ['it_desk_role' => ['nullable', Rule::enum(ItDeskRole::class)]],
        )->validate();

        $person->update([
            'it_desk_role' => filled($validated['it_desk_role'])
                ? ItDeskRole::from($validated['it_desk_role'])
                : null,
        ]);

        unset($this->people, $this->counts);

        $this->status = "Acceso a IT Desk de {$person->name} actualizado.";
    }

    public function toggleActive(int $userId): void
    {
        $person = User::query()->findOrFail($userId);

        abort_unless(auth()->user()->can('update', $person), 403);

        if ($person->is(auth()->user())) {
            $this->status = 'No puedes desactivar tu propia cuenta.';

            return;
        }

        $person->update(['is_active' => ! $person->is_active]);

        unset($this->people, $this->counts);

        $this->status = $person->is_active
            ? "{$person->name} vuelve a estar activo."
            : "{$person->name} quedó desactivado.";
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->roleFilter = '';

        unset($this->people);
    }

    public function openCreate(): void
    {
        abort_unless(auth()->user()->can('create', User::class), 403);

        $this->createAreaId = (string) Area::query()->orderBy('sort_order')->first()?->id;
        $this->dispatch('open-modal', 'admin-create-user');
    }

    public function closeCreate(): void
    {
        $this->dispatch('close-modal', name: 'admin-create-user');
        $this->reset(['createName', 'createEmail', 'createPhone', 'createJobTitle', 'createAreaId', 'createRole', 'createPassword', 'createPasswordConfirmation']);
        $this->resetErrorBag();
    }

    public function createUser(): void
    {
        abort_unless(auth()->user()->can('create', User::class), 403);

        $validated = $this->validate([
            'createName' => ['required', 'string', 'max:255'],
            'createEmail' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.',email'],
            'createPhone' => ['nullable', 'string', 'max:30', 'regex:/^[\s()+\-0-9]{7,30}$/'],
            'createJobTitle' => ['nullable', 'string', 'max:100'],
            'createAreaId' => ['nullable', 'exists:'.Area::class.',id'],
            'createRole' => ['required', Rule::enum(UserRole::class)],
            'createPassword' => ['required', 'string', Illuminate\Validation\Rules\Password::defaults()],
            'createPasswordConfirmation' => ['required', 'string', 'same:createPassword'],
        ], [
            'required' => 'Este campo es obligatorio.',
            'email' => 'Escribe un correo válido.',
            'unique' => 'Ese correo ya está asignado a otra persona.',
            'regex' => 'El teléfono solo acepta dígitos, espacios y los símbolos ( ) + -.',
            'same' => 'Las contraseñas no coinciden.',
            'min' => 'La contraseña debe tener al menos 8 caracteres.',
        ], [
            'createName' => 'nombre',
            'createEmail' => 'correo',
            'createPhone' => 'teléfono',
            'createJobTitle' => 'puesto',
            'createAreaId' => 'área',
            'createRole' => 'rol',
            'createPassword' => 'contraseña',
            'createPasswordConfirmation' => 'confirmación de contraseña',
        ]);

        User::create([
            'name' => $validated['createName'],
            'email' => $validated['createEmail'],
            'phone' => $validated['createPhone'],
            'job_title' => $validated['createJobTitle'],
            'area_id' => $validated['createAreaId'],
            'role' => UserRole::from($validated['createRole']),
            'password' => Hash::make($validated['createPassword']),
        ]);

        unset($this->people, $this->counts);
        $this->closeCreate();

        $this->status = "Cuenta creada para {$validated['createName']}. Ya puede iniciar sesión con la contraseña asignada.";
    }

    public function confirmDelete(int $userId): void
    {
        abort_unless(auth()->user()->can('delete', User::query()->findOrFail($userId)), 403);

        $this->deletingUserId = $userId;
        $this->dispatch('open-modal', 'admin-delete-user');
    }

    public function cancelDelete(): void
    {
        $this->dispatch('close-modal', name: 'admin-delete-user');
        $this->deletingUserId = null;
    }

    public function deleteUser(): void
    {
        if ($this->deletingUserId === null) {
            return;
        }

        $person = User::query()->findOrFail($this->deletingUserId);

        abort_unless(auth()->user()->can('delete', $person), 403);

        $name = $person->name;

        $person->delete();

        unset($this->people, $this->counts);
        $this->deletingUserId = null;
        $this->dispatch('close-modal', name: 'admin-delete-user');

        $this->status = "{$name} fue eliminada de Daybridge.";
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Administración</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Personas</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Roles de plataforma, acceso a IT Desk y estado de cada cuenta.
            </p>
        </div>

        <div class="flex flex-wrap items-end justify-between gap-4">
            <dl class="flex gap-5 text-right">
                <div>
                    <dt class="text-3xs tracking-label text-ink-muted uppercase">Personas</dt>
                    <dd class="font-condensed text-2xl font-black tabular-nums">{{ $this->counts['total'] }}</dd>
                </div>
                <div>
                    <dt class="text-3xs tracking-label text-ink-muted uppercase">Admin</dt>
                    <dd class="font-condensed text-2xl font-black tabular-nums">{{ $this->counts['admins'] }}</dd>
                </div>
                <div>
                    <dt class="text-3xs tracking-label text-ink-muted uppercase">IT Desk</dt>
                    <dd class="font-condensed text-2xl font-black tabular-nums">{{ $this->counts['it_desk'] }}</dd>
                </div>
            </dl>

            <button
                type="button"
                wire:click="openCreate"
                class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
            >
                <x-icon name="plus" class="h-4 w-4" />
                Crear cuenta
            </button>
        </div>
    </header>

    @if ($status !== '')
        <p class="mt-4 border-l-2 border-brand bg-brand-soft px-3 py-2 text-sm text-brand-strong" role="status">
            {{ $status }}
        </p>
    @endif

    <div class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-center">
        <label class="relative flex-1">
            <span class="sr-only">Buscar personas</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" />
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre, correo o puesto"
                class="w-full rounded-md border-line bg-surface py-2.5 pl-9 pr-3 text-sm placeholder:text-ink-disabled focus:border-brand focus:ring-brand"
            >
        </label>

        <label class="sm:w-56">
            <span class="sr-only">Filtrar por rol</span>
            <select wire:model.live="roleFilter" class="w-full rounded-md border-line bg-surface py-2.5 text-sm focus:border-brand focus:ring-brand">
                <option value="">Todos los roles</option>
                @foreach (UserRole::options() as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>

        @if ($search !== '' || $roleFilter !== '')
            <button
                type="button"
                wire:click="clearFilters"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-3 text-xs font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
            >
                <x-icon name="x" class="h-4 w-4" />
                Limpiar
            </button>
        @endif
    </div>

    <div class="mt-6 hidden grid-cols-12 gap-4 border-b border-line pb-2 text-3xs font-semibold tracking-label text-ink-muted uppercase md:grid">
        <span class="col-span-4">Persona</span>
        <span class="col-span-3">Rol</span>
        <span class="col-span-3">IT Desk</span>
        <span class="col-span-2 text-right">Cuenta</span>
    </div>

    <ul class="divide-y divide-line">
        @forelse ($this->people as $person)
            <li class="py-4" wire:key="person-{{ $person->id }}">
                <div class="grid grid-cols-1 gap-3 md:grid-cols-12 md:items-center md:gap-4">
                    <div class="col-span-4 flex min-w-0 items-center gap-3">
                        <x-avatar :user="$person" size="sm" />

                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold">
                                {{ $person->name }}
                                @if ($person->is(auth()->user()))
                                    <span class="ml-1 text-3xs font-medium tracking-label text-ink-muted uppercase">Tú</span>
                                @endif
                            </p>
                            <p class="truncate text-xs text-ink-muted">{{ $person->email }}</p>
                            <p class="mt-0.5 truncate text-xs text-ink-muted">
                                {{ $person->job_title ?? 'Sin puesto' }}@if ($person->area) · {{ $person->area->name }}@endif
                            </p>
                        </div>
                    </div>

                    <div class="col-span-3">
                        <label class="block">
                            <span class="mb-1 block text-3xs tracking-label text-ink-muted uppercase md:sr-only">Rol</span>
                            <select
                                wire:change="updateRole({{ $person->id }}, $event.target.value)"
                                @disabled($person->is(auth()->user()))
                                class="w-full rounded-md border-line bg-surface py-2 text-xs focus:border-brand focus:ring-brand disabled:cursor-not-allowed disabled:opacity-60"
                            >
                                @foreach (UserRole::options() as $value => $label)
                                    <option value="{{ $value }}" @selected($person->role->value === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="col-span-3">
                        <label class="block">
                            <span class="mb-1 block text-3xs tracking-label text-ink-muted uppercase md:sr-only">IT Desk</span>
                            <select
                                wire:change="updateItDeskRole({{ $person->id }}, $event.target.value)"
                                class="w-full rounded-md border-line bg-surface py-2 text-xs focus:border-brand focus:ring-brand"
                            >
                                <option value="">Sin acceso</option>
                                @foreach (ItDeskRole::options() as $value => $label)
                                    <option value="{{ $value }}" @selected($person->it_desk_role?->value === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                    </div>

                    <div class="col-span-2 flex items-center justify-between gap-2 md:justify-end">
                        <span @class([
                            'inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label uppercase',
                            'text-success' => $person->is_active,
                            'text-danger' => ! $person->is_active,
                        ])>
                            <span @class([
                                'h-1.5 w-1.5 rounded-full',
                                'bg-success' => $person->is_active,
                                'bg-danger' => ! $person->is_active,
                            ])></span>
                            {{ $person->is_active ? 'Activa' : 'Inactiva' }}
                        </span>

                        <button
                            type="button"
                            wire:click="toggleActive({{ $person->id }})"
                            @disabled($person->is(auth()->user()))
                            class="inline-flex min-h-9 items-center rounded-md border border-line px-2.5 text-3xs font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            {{ $person->is_active ? 'Desactivar' : 'Activar' }}
                        </button>

                        <button
                            type="button"
                            wire:click="confirmDelete({{ $person->id }})"
                            @disabled($person->is(auth()->user()))
                            class="inline-flex min-h-9 items-center rounded-md border border-line px-2.5 text-3xs font-medium text-danger transition duration-fast ease-signal hover:bg-danger-soft disabled:cursor-not-allowed disabled:opacity-40"
                            title="Eliminar cuenta"
                        >
                            <x-icon name="trash" class="h-3.5 w-3.5" />
                            <span class="sr-only">Eliminar cuenta de {{ $person->name }}</span>
                        </button>
                    </div>
                </div>
            </li>
        @empty
            <li class="py-14 text-center">
                <p class="font-expanded text-xl font-bold">No encontramos a nadie con ese filtro.</p>
                <button type="button" wire:click="clearFilters" class="mt-2 text-sm font-semibold text-accent-strong">
                    Ver todas las personas
                </button>
            </li>
        @endforelse
    </ul>

    <x-modal name="admin-create-user" max-width="2xl" focusable>
        <form wire:submit="createUser">
            <header class="mb-6">
                <h2 class="font-expanded text-xl font-bold">Crear cuenta</h2>
                <p class="mt-1 text-sm text-ink-muted">La persona podrá iniciar sesión de inmediato con la contraseña asignada.</p>
            </header>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="createName" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Nombre</label>
                    <input id="createName" type="text" wire:model="createName" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required>
                    <x-input-error :messages="$errors->get('createName')"/>
                </div>

                <div>
                    <label for="createEmail" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Correo electrónico</label>
                    <input id="createEmail" type="email" wire:model="createEmail" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required>
                    <x-input-error :messages="$errors->get('createEmail')"/>
                </div>

                <div>
                    <label for="createPhone" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Teléfono</label>
                    <input id="createPhone" type="tel" wire:model="createPhone" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" placeholder="+502 5555 5555">
                    <x-input-error :messages="$errors->get('createPhone')"/>
                </div>

                <div>
                    <label for="createJobTitle" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Puesto</label>
                    <input id="createJobTitle" type="text" wire:model="createJobTitle" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                    <x-input-error :messages="$errors->get('createJobTitle')"/>
                </div>

                <div>
                    <label for="createAreaId" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Área</label>
                    <select id="createAreaId" wire:model="createAreaId" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                        <option value="">Sin área asignada</option>
                        @foreach ($this->areas as $areaOption)
                            <option value="{{ $areaOption->id }}">{{ $areaOption->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('createAreaId')"/>
                </div>

                <div>
                    <label for="createRole" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Rol</label>
                    <select id="createRole" wire:model="createRole" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required>
                        <option value="">Elegir rol</option>
                        @foreach (UserRole::options() as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('createRole')"/>
                </div>

                <div>
                    <label for="createPassword" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Contraseña temporal</label>
                    <input id="createPassword" type="password" wire:model="createPassword" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required autocomplete="new-password">
                    <x-input-error :messages="$errors->get('createPassword')"/>
                </div>

                <div>
                    <label for="createPasswordConfirmation" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Confirmar contraseña</label>
                    <input id="createPasswordConfirmation" type="password" wire:model="createPasswordConfirmation" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required autocomplete="new-password">
                    <x-input-error :messages="$errors->get('createPasswordConfirmation')"/>
                </div>
            </div>

            <footer class="mt-6 flex justify-end gap-2">
                <button
                    type="button"
                    wire:click="closeCreate"
                    class="inline-flex min-h-10 items-center rounded-md border border-line px-4 text-sm font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="inline-flex min-h-10 items-center rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                    wire:loading.attr="disabled"
                >
                    Crear cuenta
                </button>
            </footer>
        </form>
    </x-modal>

    <x-modal name="admin-delete-user" max-width="sm">
        <h2 class="font-expanded text-xl font-bold">Eliminar cuenta</h2>

        <p class="mt-2 text-sm text-ink-muted">
            Se eliminará la cuenta y su contenido asociado en Daybridge. Esta acción no se puede deshacer.
        </p>

        <p class="mt-4 rounded-md border border-danger/30 bg-danger-soft/30 px-3 py-2 text-xs font-medium text-danger">
            Si la persona es la única administradora, no se podrá eliminar hasta asignar otro rol de administración.
        </p>

        <footer class="mt-6 flex justify-end gap-2">
            <button
                type="button"
                wire:click="cancelDelete"
                class="inline-flex min-h-10 items-center rounded-md border border-line px-4 text-sm font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
            >
                Cancelar
            </button>

            <button
                type="button"
                wire:click="deleteUser"
                class="inline-flex min-h-10 items-center rounded-md bg-danger px-4 text-sm font-semibold text-white transition duration-fast ease-signal hover:bg-danger/80"
                wire:loading.attr="disabled"
            >
                Eliminar definitivamente
            </button>
        </footer>
    </x-modal>
</div>
