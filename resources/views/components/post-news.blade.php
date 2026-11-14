@props(['post'])

@php
    $parts = $post->titleParts();
@endphp

<article class="group relative flex flex-col gap-4 bubble-glass p-5 transition duration-base ease-signal hover:border-line-strong hover:shadow-sm">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <x-area-label :area="$post->area" />
        <span class="h-1 w-1 rounded-full bg-line-strong"></span>
        <span class="font-condensed text-xs tabular-nums text-ink-muted">
            {{ $post->published_at->locale('es')->diffForHumans() }}
        </span>
        <span class="h-1 w-1 rounded-full bg-line-strong"></span>
        <span class="text-xs text-ink-muted">{{ $post->readingTime() }} min</span>
    </div>

    <h3 class="font-expanded font-display text-xl font-bold leading-tight tracking-tight sm:text-2xl">
        {{ $parts['before'] }}@if ($parts['highlight'])<span class="text-accent">{{ $parts['highlight'] }}</span>{{ $parts['after'] }}@endif
    </h3>

    <p class="max-w-prose text-sm leading-relaxed text-ink-secondary">
        {{ $post->excerpt }}
    </p>

    <div class="mt-auto flex items-center justify-between gap-3 pt-4">
        <div class="flex items-center gap-2">
            <x-avatar :user="$post->author" size="2xs" />
            <span class="text-xs text-ink-muted">{{ $post->author->name }}</span>
        </div>

        <details class="group/details">
            <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-xs font-semibold text-ink-muted transition duration-fast ease-signal hover:text-accent-strong">
                Leer completo
                <x-icon name="chevron-down" class="h-3.5 w-3.5 transition duration-base group-open/details:rotate-180" />
            </summary>

            <x-post-body :post="$post" class="mt-4 border-l-2 border-line pl-4 text-sm text-ink-secondary" />
        </details>
    </div>
</article>