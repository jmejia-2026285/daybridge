<?php

namespace App\Models;

use App\Enums\WallReactionType;
use Database\Factories\WallReactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['wall_post_id', 'user_id', 'type'])]
class WallReaction extends Model
{
    /** @use HasFactory<WallReactionFactory> */
    use HasFactory;

    public function post(): BelongsTo
    {
        return $this->belongsTo(WallPost::class, 'wall_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'type' => WallReactionType::class,
        ];
    }
}
