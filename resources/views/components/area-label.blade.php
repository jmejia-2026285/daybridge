@props(['area' => null])

@if ($area)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-2 text-xs text-ink-muted']) }}>
        <span class="h-1.5 w-1.5 shrink-0 rounded-full area-dot" style="--area-color: {{ $area->color }}"></span>
        <span>{{ $area->name }}</span>
    </span>
@endif
