<?php

use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Perfil · Daybridge')] class extends Component
{
    public User $person;

    public bool $editing = false;

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $jobTitle = '';

    public string $areaId = '';

    public function mount(User $person): void
    {
        abort_unless($person->is_active, 404);

        $this->person = $person->load('area');
    }

    public function edit(): void
    {
        abort_unless(auth()->user()->can('update', $this->person), 403);

        $this->name = $this->person->name;
        $this->email = $this->person->email;
        $this->phone = (string) $this->person->phone;
        $this->jobTitle = (string) $this->person->job_title;
        $this->areaId = (string) $this->person->area_id;
        $this->editing = true;
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('update', $this->person), 403);

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class.','.'email,'.$this->person->id],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\s()+\-0-9]{7,30}$/'],
            'jobTitle' => ['nullable', 'string', 'max:100'],
            'areaId' => ['nullable', 'exists:'.Area::class.',id'],
        ], [
            'email.unique' => 'Ese correo ya está asignado a otra persona.',
            'phone.regex' => 'El teléfono solo acepta dígitos, espacios y los símbolos ( ) + -.',
            'exists' => 'El área seleccionada no es válida.',
        ], [
            'jobTitle' => 'puesto',
            'areaId' => 'área',
        ]);

        $this->person->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'],
            'job_title' => $validated['jobTitle'],
            'area_id' => $validated['areaId'],
        ]);

        if ($this->person->wasChanged('email')) {
            $this->person->forceFill(['email_verified_at' => null])->save();
        }

        $this->person = $this->person->fresh('area');
        $this->editing = false;
        session()->flash('directory.status', 'Datos de la persona actualizados.');
    }

    public function cancelEdit(): void
    {
        $this->editing = false;
        $this->resetErrorBag();
    }

    #[Computed]
    public function colleagues(): Collection
    {
        if ($this->person->area_id === null) {
            return collect();
        }

        return User::query()
            ->active()
            ->with('area')
            ->where('area_id', $this->person->area_id)
            ->whereKeyNot($this->person->id)
            ->orderBy('name')
            ->take(6)
            ->get();
    }

    #[Computed]
    public function recentPosts(): Collection
    {
        return Post::query()
            ->published()
            ->with(['area', 'author.area'])
            ->where('user_id', $this->person->id)
            ->orderByDesc('published_at')
            ->take(3)
            ->get();
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <a
        href="{{ route('directory.index') }}"
        wire:navigate
        class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
    >
        <x-icon name="arrow-left" class="h-4 w-4" />
        Directorio
    </a>

    <header class="mt-5 flex flex-col gap-6 border-b border-line pb-6 sm:flex-row sm:items-start">
        <x-avatar :user="$person" size="xl" />

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                <span class="text-3xs font-bold tracking-label text-accent-strong uppercase">{{ $person->role->label() }}</span>
                @if ($person->area)
                    <span class="h-1 w-1 rounded-full bg-line-strong"></span>
                    <x-area-label :area="$person->area" />
                @endif
            </div>

            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">{{ $person->name }}</h1>

            <p class="mt-1 text-sm text-ink-muted">{{ $person->job_title ?? 'Sin puesto asignado' }}</p>

            <p class="mt-3 max-w-prose text-sm leading-relaxed text-ink-secondary">
                {{ $person->role->description() }}
            </p>
        </div>

        <div class="flex flex-col items-stretch gap-2 sm:min-w-56">
            <a
                href="mailto:{{ $person->email }}"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
            >
                <x-icon name="mail" class="h-4 w-4" />
                Escribir correo
            </a>

            <a
                href="{{ 'https://mail.google.com/mail/?view=cm&to='.urlencode($person->email) }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-4 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
            >
                <x-icon name="external" class="h-4 w-4" />
                Responder desde Gmail
            </a>

            <div
                x-data="{ copied: false }"
                class="inline-flex"
            >
                <button
                    type="button"
                    x-on:click="navigator.clipboard.writeText('{{ $person->email }}').then(() => { copied = true; clearTimeout(window.__directoryCopyTimer); window.__directoryCopyTimer = setTimeout(() => copied = false, 2500); })"
                    class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-md border border-line px-4 text-sm font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                >
                    <x-icon name="copy" class="h-4 w-4" />
                    <span x-text="copied ? 'Correo copiado' : 'Copiar correo'">
                        Copiar correo
                    </span>
                </button>
            </div>

            @if (auth()->user()?->can('update', $person))
                <button
                    type="button"
                    wire:click="edit"
                    class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-4 text-xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                >
                    <x-icon name="edit" class="h-4 w-4" />
                    Editar datos
                </button>
            @endif
        </div>
    </header>

    @if (session('directory.status'))
        <p class="mt-4 rounded-md border border-success/40 bg-success-soft/40 px-4 py-2.5 text-sm font-medium text-success" role="status">
            {{ session('directory.status') }}
        </p>
    @endif

    @if ($editing)
        <form wire:submit="save" class="mt-6 border border-line bubble-glass p-6" x-data x-cloak>
            <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Editar datos de la persona</h2>

            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="name" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Nombre</label>
                    <input id="name" type="text" wire:model="name" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required>
                    <x-input-error :messages="$errors->get('name')"/>
                </div>

                <div>
                    <label for="edit-email" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Correo electrónico</label>
                    <input id="edit-email" type="email" wire:model="email" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" required>
                    <x-input-error :messages="$errors->get('email')"/>
                </div>

                <div>
                    <label for="phone" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Teléfono</label>
                    <input id="phone" type="tel" wire:model="phone" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand" placeholder="+502 5555 5555">
                    <x-input-error :messages="$errors->get('phone')"/>
                </div>

                <div>
                    <label for="jobTitle" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Puesto</label>
                    <input id="jobTitle" type="text" wire:model="jobTitle" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                    <x-input-error :messages="$errors->get('jobTitle')"/>
                </div>

                <div>
                    <label for="areaId" class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Área</label>
                    <select id="areaId" wire:model="areaId" class="mt-1 w-full rounded-md border-line bg-canvas px-3 py-2.5 text-sm focus:border-brand focus:ring-brand">
                        <option value="">Sin área asignada</option>
                        @foreach (Area::query()->orderBy('sort_order')->get() as $areaOption)
                            <option value="{{ $areaOption->id }}">{{ $areaOption->name }}</option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('areaId')"/>
                </div>
            </div>

            <p class="mt-4 text-xs text-ink-muted">Si cambias el correo, la persona tendrá que verificar su cuenta de nuevo.</p>

            <div class="mt-6 flex flex-wrap justify-end gap-2">
                <button
                    type="button"
                    wire:click="cancelEdit"
                    class="inline-flex min-h-10 items-center rounded-md border border-line px-4 text-sm font-medium text-ink-muted transition duration-fast ease-signal hover:bg-sunken hover:text-ink"
                >
                    Cancelar
                </button>

                <button
                    type="submit"
                    class="inline-flex min-h-10 items-center rounded-md bg-brand px-4 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                    wire:loading.attr="disabled"
                >
                    Guardar cambios
                </button>
            </div>
        </form>
    @endif

    <div class="mt-8 grid gap-8 lg:grid-cols-12 lg:gap-10">
        <div class="lg:col-span-8">
            <section class="border border-line bg-surface">
                <header class="border-b border-line px-5 py-4">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Contacto y contexto</h2>
                </header>

                <dl class="divide-y divide-line">
                    <div class="grid gap-1 px-5 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Correo</dt>
                        <dd class="sm:col-span-2">
                            <a href="mailto:{{ $person->email }}" class="break-all text-sm font-medium text-accent-strong hover:text-accent">
                                {{ $person->email }}
                            </a>
                        </dd>
                    </div>

                    <div class="grid gap-1 px-5 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Teléfono</dt>
                        <dd class="sm:col-span-2">
                            @if ($person->phone)
                                <a href="tel:{{ preg_replace('/[^\d+]/', '', $person->phone) }}" class="text-sm font-medium text-accent-strong hover:text-accent">
                                    {{ $person->phone }}
                                </a>
                            @else
                                <span class="text-sm">Sin teléfono registrado</span>
                            @endif
                        </dd>
                    </div>

                    <div class="grid gap-1 px-5 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Área</dt>
                        <dd class="sm:col-span-2 text-sm">
                            {{ $person->area?->name ?? 'Sin área asignada' }}
                        </dd>
                    </div>

                    <div class="grid gap-1 px-5 py-4 sm:grid-cols-3 sm:gap-4">
                        <dt class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Puesto</dt>
                        <dd class="sm:col-span-2 text-sm">{{ $person->job_title ?? 'Sin puesto asignado' }}</dd>
                    </div>

                    @if ($person->birth_date)
                        <div class="grid gap-1 px-5 py-4 sm:grid-cols-3 sm:gap-4">
                            <dt class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Cumpleaños</dt>
                            <dd class="sm:col-span-2 text-sm">{{ $person->birth_date->locale('es')->translatedFormat('j \d\e F') }}</dd>
                        </div>
                    @endif

                    @if ($person->joined_at)
                        <div class="grid gap-1 px-5 py-4 sm:grid-cols-3 sm:gap-4">
                            <dt class="text-3xs font-semibold tracking-label text-ink-muted uppercase">En Daybridge desde</dt>
                            <dd class="sm:col-span-2 text-sm">
                                {{ $person->joined_at->locale('es')->translatedFormat('F Y') }}
                                <span class="text-ink-muted">· {{ $person->yearsAtCompany() }} {{ $person->yearsAtCompany() === 1 ? 'año' : 'años' }}</span>
                            </dd>
                        </div>
                    @endif

                    @if ($person->it_desk_role)
                        <div class="grid gap-1 px-5 py-4 sm:grid-cols-3 sm:gap-4">
                            <dt class="text-3xs font-semibold tracking-label text-ink-muted uppercase">IT Desk</dt>
                            <dd class="sm:col-span-2 text-sm">{{ $person->it_desk_role->label() }}</dd>
                        </div>
                    @endif
                </dl>
            </section>

            @if ($this->recentPosts->isNotEmpty())
                <section class="mt-8">
                    <div class="flex items-center gap-3 border-t border-line pt-4">
                        <span class="text-3xs font-bold tracking-label text-ink-muted uppercase">Comunicados recientes</span>
                        <span class="h-px flex-1 bg-line"></span>
                    </div>

                    <div class="mt-4 divide-y divide-line">
                        @foreach ($this->recentPosts as $post)
                            <a
                                href="{{ route('feed.show', $post) }}"
                                wire:navigate
                                wire:key="profile-post-{{ $post->id }}"
                                class="group flex items-start justify-between gap-4 py-4 transition duration-fast ease-signal hover:text-accent-strong"
                            >
                                <div class="min-w-0">
                                    <p class="text-sm font-semibold leading-snug">{{ $post->title }}</p>
                                    <p class="mt-1 font-condensed text-3xs tabular-nums text-ink-muted">
                                        {{ $post->published_at?->locale('es')->translatedFormat('j M Y') }}
                                        @if ($post->area)
                                            · {{ $post->area->name }}
                                        @endif
                                    </p>
                                </div>

                                <x-icon name="arrow-up-right" class="mt-0.5 h-4 w-4 shrink-0 text-ink-muted transition duration-fast ease-signal group-hover:text-accent-strong" />
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>

        <aside class="lg:col-span-4">
            <div class="border border-line bg-surface lg:sticky lg:top-20">
                <header class="flex items-baseline justify-between gap-3 border-b border-line px-5 py-4">
                    <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Compañeros de área</h2>
                    @if ($person->area)
                        <span class="font-condensed text-xs tabular-nums text-ink-muted">{{ $this->colleagues->count() }}</span>
                    @endif
                </header>

                @forelse ($this->colleagues as $colleague)
                    <a
                        href="{{ route('directory.show', $colleague) }}"
                        wire:navigate
                        wire:key="colleague-{{ $colleague->id }}"
                        class="flex items-center gap-3 border-b border-line px-5 py-3 transition duration-fast ease-signal last:border-b-0 hover:bg-sunken"
                    >
                        <x-avatar :user="$colleague" size="sm" />

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">{{ $colleague->name }}</p>
                            <p class="truncate text-xs text-ink-muted">{{ $colleague->job_title ?? 'Sin puesto asignado' }}</p>
                        </div>

                        <x-icon name="chevron-right" class="h-4 w-4 shrink-0 text-ink-muted" />
                    </a>
                @empty
                    <p class="px-5 py-4 text-sm text-ink-muted">
                        {{ $person->area ? 'No hay otras personas activas en esta área.' : 'Esta persona todavía no tiene un área asignada.' }}
                    </p>
                @endforelse
            </div>
        </aside>
    </div>
</div>
