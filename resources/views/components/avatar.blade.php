@props(['user', 'size' => 'md'])

@php
    $sizes = [
        '2xs' => 'h-6 w-6 text-[0.5rem]',
        'xs' => 'h-8 w-8 text-xs',
        'sm' => 'h-9 w-9 text-xs',
        'md' => 'h-12 w-12 text-sm',
        'lg' => 'h-20 w-20 text-xl',
        'xl' => 'h-28 w-28 text-3xl',
        '2xl' => 'h-36 w-36 text-4xl',
    ];

    $avatarPath = $user->avatar ?? null;
    $hasAvatar = $avatarPath && $avatarPath !== 'null';
@endphp

<div class="relative inline-block">
    @if($hasAvatar)
        <img
            src="{{ asset($avatarPath) }}"
            alt="{{ $user->name }}"
            {{ $attributes->merge(['class' => $sizes[$size].' inline-block rounded-full object-cover border']) }}
        />
    @else
        <span
            style="--area-color: {{ $user->area?->color ?? '#0F3D3E' }}"
            {{ $attributes->merge(['class' => $sizes[$size].' avatar-initials inline-flex shrink-0 items-center justify-center rounded-full border font-expanded font-bold tracking-tight']) }}
        >
            {{ $user->initials() }}
        </span>
    @endif
</div>