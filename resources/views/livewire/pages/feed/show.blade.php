<?php

use App\Models\Post;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Comunicado · Daybridge')] class extends Component
{
    public Post $post;

    public function mount(Post $post): void
    {
        abort_unless(auth()->user()->can('view', $post), 403);

        $this->post = $post->load(['area', 'author.area']);
    }

    #[Computed]
    public function related(): Collection
    {
        return Post::query()
            ->published()
            ->with(['area', 'author.area'])
            ->whereKeyNot($this->post->id)
            ->when(
                $this->post->area_id !== null,
                fn ($query) => $query->where('area_id', $this->post->area_id),
            )
            ->orderByDesc('published_at')
            ->take(3)
            ->get();
    }
}; ?>

@php
    $parts = $post->titleParts();
@endphp

<div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10">
    <a
        href="{{ route('feed.index') }}"
        wire:navigate
        class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase transition duration-fast ease-signal hover:text-accent-strong"
    >
        <x-icon name="arrow-left" class="h-4 w-4" />
        Comunicados
    </a>

    <article class="mt-5">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <span class="text-3xs font-bold tracking-label text-accent-strong uppercase">{{ $post->typeLabel() }}</span>
            <span class="h-1 w-1 rounded-full bg-line-strong"></span>
            <x-area-label :area="$post->area" />
            <span class="font-condensed text-xs tabular-nums text-ink-muted">
                {{ $post->published_at?->locale('es')->translatedFormat('j M Y · H:i') }}
            </span>
            <span class="h-1 w-1 rounded-full bg-line-strong"></span>
            <span class="text-xs text-ink-muted">{{ $post->readingTime() }} min de lectura</span>
        </div>

        <h1 class="mt-4 max-w-5xl font-expanded font-display text-4xl font-black leading-[0.95] tracking-display sm:text-6xl lg:text-7xl">
            {{ $parts['before'] }}@if ($parts['highlight'])<span class="text-accent">{{ $parts['highlight'] }}</span>{{ $parts['after'] }}@endif
        </h1>

        <p class="mt-5 max-w-prose text-base text-ink-secondary sm:text-lg">
            {{ $post->excerpt }}
        </p>

        <div class="mt-6 flex flex-wrap items-center gap-3 border-y border-line py-4">
            <x-avatar :user="$post->author" size="sm" />
            <div class="leading-tight">
                <p class="text-sm font-medium">{{ $post->author->name }}</p>
                <p class="text-xs text-ink-muted">{{ $post->author->job_title ?? $post->area?->name }}</p>
            </div>
        </div>

        <x-post-body :post="$post" class="mt-6 max-w-prose text-base" />
    </article>

    @if ($this->related->isNotEmpty())
        <section class="mt-12 border-t border-line pt-6">
            <div class="flex items-center gap-3">
                <span class="text-3xs font-bold tracking-label text-ink-muted uppercase">Más de {{ $post->area?->name ?? 'la plataforma' }}</span>
                <span class="h-px flex-1 bg-line"></span>
            </div>

            <div class="mt-5 divide-y divide-line">
                @foreach ($this->related as $related)
                    <div class="py-5" wire:key="related-{{ $related->id }}">
                        <x-post-entry :post="$related" />

                        <a
                            href="{{ route('feed.show', $related) }}"
                            wire:navigate
                            class="mt-3 inline-flex items-center gap-1.5 text-xs font-semibold text-accent-strong transition duration-fast ease-signal hover:text-accent"
                        >
                            Abrir comunicado
                            <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                        </a>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
