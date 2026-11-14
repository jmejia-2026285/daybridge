@props(['showHours' => true])

@php
    $now = now();
    $progress = (($now->hour * 60) + $now->minute) / 1440 * 100;
@endphp

<div {{ $attributes->merge(['class' => 'relative']) }} aria-hidden="true">
    <div class="relative h-px w-full bg-line">
        <span
            class="absolute -top-[3px] h-[7px] w-[7px] -translate-x-1/2 rounded-full bg-accent animate-now"
            style="left: {{ $progress }}%"
        ></span>

        <span
            class="absolute -top-5 -translate-x-1/2 text-3xs font-semibold tracking-label text-accent-strong uppercase"
            style="left: clamp(1.25rem, {{ $progress }}%, calc(100% - 1.25rem))"
        >
            Ahora
        </span>

        @foreach ([25, 50, 75] as $tick)
            <span class="absolute -top-[2px] h-[5px] w-px bg-line-strong" style="left: {{ $tick }}%"></span>
        @endforeach
    </div>

    @if ($showHours)
        <div class="mt-2 flex justify-between text-3xs font-medium tracking-label text-ink-muted uppercase">
            <span>00</span>
            <span>06</span>
            <span>12</span>
            <span>18</span>
            <span>24</span>
        </div>
    @endif
</div>
