@props(['post'])

<div {{ $attributes->merge(['class' => 'space-y-4 text-sm leading-relaxed text-ink-secondary']) }}>
    @foreach (preg_split('/\n{2,}/', trim($post->body)) as $paragraph)
        <p>{{ $paragraph }}</p>
    @endforeach
</div>
