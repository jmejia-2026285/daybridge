@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-2xs font-semibold tracking-label text-ink-muted uppercase']) }}>
    {{ $value ?? $slot }}
</label>
