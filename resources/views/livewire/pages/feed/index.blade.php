<?php

use App\Models\Area;
use App\Models\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.app')] #[Title('Comunicados · Daybridge')] class extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'area')]
    public string $area = '';

    #[Url(as: 'tipo')]
    public string $type = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('viewAny', Post::class), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'area', 'type'], true)) {
            $this->resetPage();
        }
    }

    #[Computed]
    public function posts()
    {
        return Post::query()
            ->published()
            ->with(['area', 'author.area'])
            ->when($this->search !== '', fn (Builder $query): Builder => $query->where(
                fn (Builder $query): Builder => $query
                    ->where('title', 'like', "%{$this->search}%")
                    ->orWhere('excerpt', 'like', "%{$this->search}%")
                    ->orWhere('body', 'like', "%{$this->search}%"),
            ))
            ->when($this->area !== '', fn (Builder $query): Builder => $query->whereHas(
                'area',
                fn (Builder $query): Builder => $query->where('slug', $this->area),
            ))
            ->when($this->type !== '', fn (Builder $query): Builder => $query->where('type', $this->type))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->paginate(10);
    }

    #[Computed]
    public function areas(): Collection
    {
        return Area::query()
            ->withCount(['posts' => fn (Builder $query): Builder => $query->published()])
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return array<string, string>
     */
    #[Computed]
    public function types(): array
    {
        return [
            Post::TYPE_NEWS => 'Noticias',
            Post::TYPE_NOTICE => 'Avisos',
            Post::TYPE_RECOGNITION => 'Reconocimientos',
        ];
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->area = '';
        $this->type = '';

        $this->resetPage();
    }
}; ?>

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <header class="flex flex-wrap items-start justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Comunicación interna</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Comunicados</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Noticias, avisos y reconocimientos de todas las áreas, en orden de publicación.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if (auth()->user()->can('create', Post::class))
                <a
                    href="{{ route('admin.content') }}"
                    wire:navigate
                    class="inline-flex min-h-10 items-center gap-2 rounded-md bg-brand px-3.5 text-sm font-semibold text-brand-foreground transition duration-fast ease-signal hover:bg-brand-strong"
                >
                    <x-icon name="settings" class="h-4 w-4" />
                    Gestionar
                </a>
            @endif

            <a
                href="{{ route('wall.index') }}"
                wire:navigate
                class="inline-flex min-h-10 items-center gap-2 rounded-md border border-line px-3.5 text-sm font-medium text-ink transition duration-fast ease-signal hover:bg-sunken"
            >
                <x-icon name="chat" class="h-4 w-4" />
                Ir al muro
            </a>
        </div>
    </header>

    <div class="mt-6 flex flex-col gap-3 bubble-glass p-4 sm:flex-row sm:items-center">
        <label class="relative flex-1">
            <span class="sr-only">Buscar comunicados</span>
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-ink-muted" />
            <input
                type="search"
                wire:model.live.debounce.300ms="search"
                placeholder="Buscar por título, resumen o contenido"
                class="w-full rounded-md border-line bg-canvas py-2.5 pl-9 pr-3 text-sm placeholder:text-ink-disabled focus:border-brand focus:ring-brand"
            >
        </label>

        <label class="sm:w-48">
            <span class="sr-only">Filtrar por área</span>
            <select
                wire:model.live="area"
                class="w-full rounded-md border-line bg-canvas py-2.5 text-sm focus:border-brand focus:ring-brand"
            >
                <option value="">Todas las áreas</option>
                @foreach ($this->areas as $areaOption)
                    <option value="{{ $areaOption->slug }}">{{ $areaOption->name }} ({{ $areaOption->posts_count }})</option>
                @endforeach
            </select>
        </label>

        <label class="sm:w-44">
            <span class="sr-only">Filtrar por tipo</span>
            <select
                wire:model.live="type"
                class="w-full rounded-md border-line bg-canvas py-2.5 text-sm focus:border-brand focus:ring-brand"
            >
                <option value="">Todos los tipos</option>
                @foreach ($this->types as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </label>
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-ink-muted">
            {{ $this->posts->total() }} {{ $this->posts->total() === 1 ? 'comunicado' : 'comunicados' }}
        </p>

        <button
            type="button"
            wire:click="clearFilters"
            class="text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
        >
            Limpiar filtros
        </button>
    </div>

    @if ($this->posts->isNotEmpty())
        <div class="mt-4 divide-y divide-line border-t border-line">
            @foreach ($this->posts as $post)
                <div class="py-7" wire:key="feed-post-{{ $post->id }}">
                    <x-post-entry :post="$post" />

                    <a
                        href="{{ route('feed.show', $post) }}"
                        wire:navigate
                        class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-accent-strong transition duration-fast ease-signal hover:text-accent"
                    >
                        Abrir comunicado
                        <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                    </a>
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $this->posts->links() }}
        </div>
    @else
        <div class="mt-6 flex flex-col items-start gap-4 bubble-glass p-6 sm:flex-row sm:items-center">
            <img src="{{ asset('media/rem/rem_duda.png') }}" alt="" class="h-24 w-auto">
            <div>
                <p class="font-expanded text-xl font-bold">No encontramos comunicados con esos filtros.</p>
                <p class="mt-1 text-sm text-ink-muted">Prueba con otra búsqueda o limpia los filtros para ver todo.</p>
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
