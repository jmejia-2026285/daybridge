@props(['value', 'width' => 330, 'height' => 74])

<div {{ $attributes->merge(['class' => 'inline-block']) }}>
    {!! \App\Support\Code128::svg($value, $width, $height) !!}
</div>
