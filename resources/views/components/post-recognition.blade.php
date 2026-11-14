@props(['post'])

@php
    $parts = $post->titleParts();
@endphp

<article class="group relative flex flex-col gap-4 bubble-glass p-5 transition duration-base ease-signal hover:-translate-y-1 hover:border-line-strong hover:shadow-sm">
    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
        <span class="rounded-full bg-accent-soft px-2 py-0.5 text-[10px] font-bold uppercase tracking-label text-accent-strong">
            Reconocimiento
        </span>
        <span class="h-1 w-1 rounded-full bg-line-strong"></span>
        <x-area-label :area="$post->area" />
        <span class="font-condensed text-xs tabular-nums text-ink-muted">
            {{ $post->published_at->locale('es')->diffForHumans() }}
        </span>
    </div>

    <h3 class="font-expanded font-display text-xl font-bold leading-tight tracking-tight">
        {{ $parts['before'] }}@if ($parts['highlight'])<span class="text-accent">{{ $parts['highlight'] }}</span>{{ $parts['after'] }}@endif
    </h3>

    <p class="max-w-prose text-sm leading-relaxed text-ink-secondary">
        {{ $post->excerpt }}
    </p>

    <div class="mt-auto flex items-center gap-2 pt-4">
        <x-avatar :user="$post->author" size="2xs" />
        <span class="text-xs text-ink-muted">{{ $post->author->name }}</span>
    </div>
</article>