<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['area_id', 'user_id', 'title', 'highlight', 'excerpt', 'body', 'image_path', 'type', 'is_featured', 'is_urgent', 'published_at'])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    public const TYPE_NEWS = 'news';

    public const TYPE_NOTICE = 'notice';

    public const TYPE_RECOGNITION = 'recognition';

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('published_at', '<=', now());
    }

    public function scopeUrgent(Builder $query): Builder
    {
        return $query->where('is_urgent', true);
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_NOTICE => 'Aviso',
            self::TYPE_RECOGNITION => 'Reconocimiento',
            default => 'Noticia',
        };
    }

    public function readingTime(): int
    {
        return max(1, (int) ceil(str_word_count(strip_tags($this->body)) / 200));
    }

    public function getUrl(): string
    {
        return route('feed.show', $this);
    }

    /**
     * @return array{before: string, highlight: ?string, after: ?string}
     */
    public function titleParts(): array
    {
        if (blank($this->highlight) || ! str_contains($this->title, $this->highlight)) {
            return ['before' => $this->title, 'highlight' => null, 'after' => null];
        }

        [$before, $after] = explode($this->highlight, $this->title, 2);

        return ['before' => $before, 'highlight' => $this->highlight, 'after' => $after];
    }

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_urgent' => 'boolean',
            'published_at' => 'datetime',
        ];
    }
}
