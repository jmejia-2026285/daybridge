<?php

use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Inicio · Daybridge')] class extends Component
{
    #[Url(as: 'area')]
    public ?string $area = null;

    #[On('birthday-wish-sent')]
    public function refreshCelebrations(): void
    {
        unset($this->celebrating);
    }

    #[Computed]
    public function posts(): Collection
    {
        return Post::query()
            ->published()
            ->with(['area', 'author.area'])
            ->when($this->area, fn (Builder $query): Builder => $query->whereHas(
                'area',
                fn (Builder $query): Builder => $query->where('slug', $this->area),
            ))
            ->orderByDesc('is_featured')
            ->orderByDesc('published_at')
            ->take(9)
            ->get();
    }

    #[Computed]
    public function lead(): ?Post
    {
        return $this->posts->first(
            fn (Post $post): bool => $post->is_featured || $post->type === Post::TYPE_NEWS,
        );
    }

    #[Computed]
    public function feed(): Collection
    {
        return $this->posts
            ->reject(fn (Post $post): bool => $this->lead?->is($post) ?? false)
            ->values();
    }

    #[Computed]
    public function areas(): Collection
    {
        return Area::query()
            ->withCount(['posts' => fn (Builder $query): Builder => $query->published()])
            ->orderBy('sort_order')
            ->get();
    }

    #[Computed]
    public function urgent(): Collection
    {
        return Post::query()
            ->published()
            ->urgent()
            ->with(['area', 'author.area'])
            ->orderByDesc('published_at')
            ->take(3)
            ->get();
    }

    #[Computed]
    public function celebrating(): Collection
    {
        return User::celebratingToday();
    }

    #[Computed]
    public function upcoming(): Collection
    {
        return User::upcomingBirthdays(30);
    }

    #[Computed]
    public function firstName(): string
    {
        return Str::of(auth()->user()->name)->explode(' ')->first();
    }

    #[Computed]
    public function weekday(): string
    {
        return today()->locale('es')->translatedFormat('l');
    }

    #[Computed]
    public function dateNumber(): string
    {
        return today()->format('d');
    }

    #[Computed]
    public function dateMonth(): string
    {
        return today()->locale('es')->translatedFormat('M');
    }

    #[Computed]
    public function dateLabel(): string
    {
        return today()->locale('es')->translatedFormat('D j M');
    }
}; ?>

<div data-anim="page">
    <section class="mx-auto max-w-content px-4 pt-8 sm:px-6 md:px-10 md:pt-10" data-anim="header-section">
        <div class="flex items-start justify-between gap-6">
            <div>
                <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase" data-anim="header-item">
                    {{ $this->weekday }}
                </p>

                <p class="mt-1 flex items-baseline gap-2" data-anim="header-item">
                    <span class="font-condensed text-6xl font-black leading-none tabular-nums sm:text-7xl">
                        {{ $this->dateNumber }}
                    </span>
                    <span class="font-expanded text-xl font-bold uppercase tracking-label sm:text-2xl">
                        {{ $this->dateMonth }}
                    </span>
                </p>
            </div>

            <div class="max-w-[16rem] text-right" data-anim="header-item">
                <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">
                    Edición de hoy
                </p>
                <p class="mt-1 font-expanded text-lg font-bold leading-tight sm:text-xl">
                    Hola, {{ $this->firstName }}.
                </p>
                <p class="mt-1 text-xs text-ink-muted">
                    Lo que está pasando en Daybridge.
                </p>
            </div>
        </div>

        <x-dayline class="mt-8" data-anim="header-item" />
    </section>

    <nav class="mx-auto mt-6 flex max-w-content gap-5 overflow-x-auto px-4 pb-1 sm:px-6 md:px-10" aria-label="Filtrar por área" data-anim="categories">
        <a
            href="{{ route('home') }}"
            wire:navigate
            data-anim="category"
            @class([
                'flex shrink-0 items-center gap-2 border-b pb-1.5 text-xs font-medium transition duration-fast ease-signal',
                'border-ink text-ink' => blank($this->area),
                'border-transparent text-ink-muted hover:border-line-strong hover:text-ink' => filled($this->area),
            ])
        >
            Todas
            <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                {{ $this->areas->sum('posts_count') }}
            </span>
        </a>

        @foreach ($this->areas as $area)
            <a
                href="{{ route('home', ['area' => $area->slug]) }}"
                wire:navigate
                data-anim="category"
                @class([
                    'flex shrink-0 items-center gap-2 border-b pb-1.5 text-xs font-medium transition duration-fast ease-signal',
                    'border-ink text-ink' => $this->area === $area->slug,
                    'border-transparent text-ink-muted hover:border-line-strong hover:text-ink' => $this->area !== $area->slug,
                ])
            >
                <span class="h-1.5 w-1.5 rounded-full area-dot" style="--area-color: {{ $area->color }}"></span>
                {{ $area->name }}
                <span class="font-condensed text-3xs tabular-nums text-ink-muted">
                    {{ $area->posts_count }}
                </span>
            </a>
        @endforeach
    </nav>

    <section class="mx-auto max-w-content px-4 pt-8 sm:px-6 md:px-10">
        @if ($this->lead)
            <div class="flex items-center gap-3 border-t border-line pt-4" data-anim="lead-label">
                <span class="text-3xs font-bold tracking-label text-ink-muted uppercase">Portada</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>

            <div class="mt-6" data-anim="lead">
                <x-post-lead :post="$this->lead" />
            </div>
        @endif
    </section>

    <div class="mx-auto mt-10 grid max-w-content grid-cols-1 gap-10 px-4 pb-16 sm:px-6 md:px-10 lg:grid-cols-12 lg:gap-12">
        <aside class="order-first lg:order-last lg:col-span-4 lg:sticky lg:top-8 lg:self-start">
            <div class="bubble-glass">
                <header class="border-b border-line px-5 py-4">
                    <div class="flex items-baseline justify-between gap-3">
                        <h2 class="font-expanded text-sm font-bold tracking-label uppercase">Hoy</h2>
                        <p class="font-condensed text-xs tabular-nums text-ink-muted">{{ $this->dateLabel }}</p>
                    </div>
                </header>

                <section class="border-b border-line px-5 py-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Cumpleaños</h3>
                        <a href="{{ route('birthdays') }}" wire:navigate class="text-3xs font-medium text-ink-muted transition duration-fast ease-signal hover:text-accent-strong">
                            Ver todos
                        </a>
                    </div>

                    @forelse ($this->celebrating as $person)
                        <div class="mt-3 flex gap-3" wire:key="celebrating-{{ $person->id }}">
                            <x-avatar :user="$person" size="md" />

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-semibold">{{ $person->name }}</p>
                                <x-area-label :area="$person->area" class="mt-0.5" />

                                <div class="mt-2">
                                    <livewire:birthday-wish-button :recipient="$person" :key="'wish-home-'.$person->id" />
                                </div>
                            </div>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-ink-muted">Hoy no hay velas que soplar.</p>

                        @if ($this->upcoming->isNotEmpty())
                            <p class="mt-1 text-xs text-ink-muted">
                                El próximo es {{ $this->upcoming->first()->name }}, {{ $this->upcoming->first()->daysUntilBirthday() === 1 ? 'mañana' : 'en '.$this->upcoming->first()->daysUntilBirthday().' días' }}.
                            </p>
                        @endif
                    @endforelse
                </section>

                @if ($this->urgent->isNotEmpty())
                    <section class="border-b border-line px-5 py-4">
                        <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Avisos urgentes</h3>

                        <ul class="mt-3 space-y-3">
                            @foreach ($this->urgent as $post)
                                <li class="flex gap-2.5" wire:key="urgent-{{ $post->id }}">
                                    <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full area-dot" style="--area-color: {{ $post->area->color }}"></span>

                                    <div class="min-w-0">
                                        <p class="text-sm font-medium leading-snug">{{ $post->title }}</p>
                                        <p class="mt-0.5 font-condensed text-3xs tabular-nums text-ink-muted">
                                            {{ $post->published_at->locale('es')->diffForHumans() }}
                                        </p>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif

                <section class="px-5 py-4">
                    <h3 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Próximos</h3>

                    @forelse ($this->upcoming->take(3) as $person)
                        <div class="mt-3 flex items-center gap-3" wire:key="upcoming-{{ $person->id }}">
                            <div class="w-9 shrink-0 text-center">
                                <p class="font-condensed text-lg font-black leading-none tabular-nums">
                                    {{ $person->nextBirthday()->format('j') }}
                                </p>
                                <p class="mt-0.5 text-3xs tracking-label text-ink-muted uppercase">
                                    {{ $person->nextBirthday()->locale('es')->translatedFormat('M') }}
                                </p>
                            </div>

                            <x-avatar :user="$person" size="sm" />

                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium">{{ $person->name }}</p>
                                <x-area-label :area="$person->area" class="mt-0.5" />
                            </div>
                        </div>
                    @empty
                        <p class="mt-3 text-sm text-ink-muted">Sin fechas cercanas.</p>
                    @endforelse
                </section>
            </div>
        </aside>

    <div class="lg:col-span-8">
        <div class="flex items-center gap-3 border-t border-line pt-4">
            <span class="text-3xs font-bold tracking-label text-ink-muted uppercase">Últimos comunicados</span>
            <span class="h-px flex-1 bg-line"></span>
            <a
                href="{{ route('feed.index') }}"
                wire:navigate
                class="text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
            >
                Ver todos
            </a>
        </div>

        @if ($this->feed->isNotEmpty())
            <div class="mt-6 grid gap-4">
                @foreach ($this->feed as $post)
                    <div wire:key="post-{{ $post->id }}" class="group relative transition duration-base ease-signal hover:-translate-y-1">
                        <x-post-entry :post="$post" />
                    </div>
                @endforeach
            </div>
        @else
            <div class="py-14">
                <p class="font-expanded font-display text-2xl font-bold">
                    Todavía no hay comunicados en esta área.
                </p>

                <a href="{{ route('home') }}" wire:navigate class="mt-3 inline-flex items-center gap-1.5 text-sm font-semibold text-accent-strong">
                    Ver todas las publicaciones
                    <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        @endif
    </div>
    </div>
</div>
