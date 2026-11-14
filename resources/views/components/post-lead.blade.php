@props(['post'])

@php
    $parts = $post->titleParts();
@endphp

<article class="relative flex flex-col gap-6 bubble-glass p-6 sm:p-8 shadow-sm transition duration-base ease-signal hover:border-line-strong">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
        <x-area-label :area="$post->area" />
        <span class="h-1 w-1 rounded-full bg-line-strong"></span>
        <span class="font-condensed text-xs tabular-nums text-ink-muted">
            {{ $post->published_at->locale('es')->diffForHumans() }}
        </span>
        <span class="h-1 w-1 rounded-full bg-line-strong"></span>
        <span class="text-xs text-ink-muted">{{ $post->readingTime() }} min de lectura</span>
    </div>

    <div class="overflow-hidden pb-2">
        <h2 data-anim="lead-title" class="font-expanded font-display text-4xl font-black leading-[0.95] tracking-display sm:text-6xl lg:text-7xl">
            {{ $parts['before'] }}@if ($parts['highlight'])<span class="text-accent">{{ $parts['highlight'] }}</span>{{ $parts['after'] }}@endif
        </h2>
    </div>

    <p class="max-w-prose text-base leading-relaxed text-ink-secondary sm:text-lg">
        {{ $post->excerpt }}
    </p>

    <div class="flex items-center gap-4 border-t border-line pt-6">
        <x-avatar :user="$post->author" size="md" />
        <div class="leading-tight">
            <p class="text-sm font-bold">{{ $post->author->name }}</p>
            <p class="text-xs text-ink-muted">{{ $post->author->job_title }}</p>
        </div>
    </div>

    <details class="group/details">
        <summary class="inline-flex cursor-pointer list-none items-center gap-1.5 text-sm font-semibold text-ink-muted transition duration-fast ease-signal hover:text-accent-strong">
            Leer completo
            <x-icon name="chevron-down" class="h-4 w-4 transition duration-base group-open/details:rotate-180" />
        </summary>

        <x-post-body :post="$post" class="mt-4 max-w-prose border-l-2 border-line pl-4 sm:pl-5 text-base text-ink-secondary" />
    </details>
</article>