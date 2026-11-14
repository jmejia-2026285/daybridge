<?php

use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Directorio · Daybridge')] class extends Component
{
    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'area')]
    public string $area = '';

    #[Computed]
    public function people(): Collection
    {
        return User::query()
            ->active()
            ->with('area')
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$this->search}%")
                    ->orWhere('email', 'like', "%{$this->search}%")
                    ->orWhere('job_title', 'like', "%{$this->search}%")
                    ->orWhereHas('area', fn (Builder $query): Builder => $query->where('name', 'like', "%{$this->search}%")),
            ))
            ->when($this->area !== '', fn (Builder $query): Builder => $query->whereHas(
                'area',
                fn (Builder $query): Builder => $query->where('slug', $this->area),
            ))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<string, Collection<int, User>>
     */
    #[Computed]
    public function groups(): Collection
    {
        return $this->people
            ->groupBy(fn (User $person): string => mb_strtoupper(mb_substr($person->name, 0, 1)))
            ->sortKeys();
    }

    #[Computed]
    public function areas(): Collection
    {
        return Area::query()
            ->withCount(['users' => fn (Builder $query): Builder => $query->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->area = '';
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Personas</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Directorio</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Encuentra a quién necesitas por nombre, puesto o área. Cada perfil reúne el contacto y el contexto del equipo.
            </p>
        </div>

        <p class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3 text-xs font-semibold tracking-label text-ink-muted uppercase">
            <x-icon name="users" class="h-4 w-4" />
            {{ $this->people->count() }} {{ $this->people->count() === 1 ? 'persona' : 'personas' }}
        </p>
    </header>

    <div class="mt-6 bubble-glass p-4">
        <label class="relative block">
            <span class="sr-only">Buscar personas</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" />
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por nombre, puesto, área o correo"
                class="w-full rounded-md border-line bg-canvas py-2.5 pl-9 pr-3 text-sm placeholder:text-ink-disabled focus:border-brand focus:ring-brand"
            >
        </label>
    </div>

    <nav class="mt-4 flex gap-5 overflow-x-auto pb-1" aria-label="Filtrar por área">
        <button
            type="button"
            wire:click="$set('area', '')"
            @class([
                'flex shrink-0 items-center gap-2 border-b pb-1.5 text-xs font-medium transition duration-fast ease-signal',
                'border-ink text-ink' => blank($area),
                'border-transparent text-ink-muted hover:border-line-strong hover:text-ink' => filled($area),
            ])
        >
            Todas
            <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                {{ $this->areas->sum('users_count') }}
            </span>
        </button>

        @foreach ($this->areas as $areaOption)
            <button
                type="button"
                wire:click="$set('area', '{{ $areaOption->slug }}')"
                @class([
                    'flex shrink-0 items-center gap-2 border-b pb-1.5 text-xs font-medium transition duration-fast ease-signal',
                    'border-ink text-ink' => $area === $areaOption->slug,
                    'border-transparent text-ink-muted hover:border-line-strong hover:text-ink' => $area !== $areaOption->slug,
                ])
            >
                <span class="h-1.5 w-1.5 rounded-full area-dot" style="--area-color: {{ $areaOption->color }}"></span>
                {{ $areaOption->name }}
                <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                    {{ $areaOption->users_count }}
                </span>
            </button>
        @endforeach
    </nav>

    @if ($this->people->isNotEmpty())
        <div class="mt-8 space-y-10">
            @foreach ($this->groups as $letter => $people)
                <section wire:key="directory-letter-{{ $letter }}">
                    <div class="flex items-center gap-3">
                        <span class="font-condensed text-2xl font-black tabular-nums">{{ $letter }}</span>
                        <span class="h-px flex-1 bg-line"></span>
                        <span class="text-3xs tracking-label text-ink-muted uppercase">{{ $people->count() }}</span>
                    </div>

                    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach ($people as $person)
                            <a
                                href="{{ route('directory.show', $person) }}"
                                wire:navigate
                                wire:key="directory-person-{{ $person->id }}"
                                class="group flex gap-4 border border-line bg-surface p-4 transition duration-fast ease-signal hover:border-line-strong hover:bg-sunken"
                            >
                                <x-avatar :user="$person" size="md" />

                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold transition duration-fast ease-signal group-hover:text-accent-strong">
                                        {{ $person->name }}
                                    </p>

                                    <p class="mt-0.5 truncate text-xs text-ink-muted">
                                        {{ $person->job_title ?? 'Sin puesto asignado' }}
                                    </p>

                                    @if ($person->area)
                                        <x-area-label :area="$person->area" class="mt-1.5" />
                                    @endif

                                    <p class="mt-2 flex items-center gap-1.5 truncate text-xs text-ink-muted">
                                        <x-icon name="mail" class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">{{ $person->email }}</span>
                                    </p>

                                    @if ($person->phone)
                                        <p class="mt-1 flex items-center gap-1.5 truncate text-xs text-ink-muted">
                                            <x-icon name="phone" class="h-3.5 w-3.5 shrink-0" />
                                            <span class="truncate">{{ $person->phone }}</span>
                                        </p>
                                    @endif
                                </div>

                                <x-icon name="arrow-up-right" class="h-4 w-4 shrink-0 text-ink-muted transition duration-fast ease-signal group-hover:text-accent-strong" />
                            </a>
                        @endforeach
                    </div>
                </section>
            @endforeach
        </div>
    @else
        <div class="mt-8 flex flex-col items-start gap-4 border border-line bg-surface p-6 sm:flex-row sm:items-center">
            <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-24 w-auto">
            <div>
                <p class="font-expanded text-xl font-bold">No encontramos a nadie con esos filtros.</p>
                <p class="mt-1 text-sm text-ink-muted">Prueba con otro nombre o cambia el área seleccionada.</p>
                <button
                    type="button"
                    wire:click="clearFilters"
                    class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-strong"
                >
                    Limpiar filtros
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </button>
            </div>
        </div>
    @endif
</div>
