<?php

namespace App\Models;

use App\Enums\WallReactionType;
use Database\Factories\WallPostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'body', 'is_pinned'])]
class WallPost extends Model
{
    /** @use HasFactory<WallPostFactory> */
    use HasFactory;

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(WallReaction::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(WallComment::class)->oldest();
    }

    public function getUrl(): string
    {
        return route('wall.index');
    }

    public function scopePinnedFirst(Builder $query): Builder
    {
        return $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    public function reactionBy(User $user): ?WallReaction
    {
        return $this->reactions->firstWhere('user_id', $user->id);
    }

    /**
     * @return array<string, int>
     */
    public function reactionCounts(): array
    {
        $counts = array_fill_keys(array_keys(WallReactionType::options()), 0);

        foreach ($this->reactions as $reaction) {
            $counts[$reaction->type->value] = ($counts[$reaction->type->value] ?? 0) + 1;
        }

        return $counts;
    }

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
        ];
    }
}
