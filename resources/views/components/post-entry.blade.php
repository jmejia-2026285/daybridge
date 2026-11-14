@props(['post'])

@if ($post->type === \App\Models\Post::TYPE_NOTICE)
    <x-post-notice :post="$post" />
@elseif ($post->type === \App\Models\Post::TYPE_RECOGNITION)
    <x-post-recognition :post="$post" />
@else
    <x-post-news :post="$post" />
@endif
