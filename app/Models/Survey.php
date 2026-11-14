<?php

namespace App\Models;

use Database\Factories\SurveyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable(['user_id', 'title', 'description', 'closes_at', 'is_active'])]
class Survey extends Model
{
    /** @use HasFactory<SurveyFactory> */
    use HasFactory;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(SurveyQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function votes(): HasManyThrough
    {
        return $this->hasManyThrough(SurveyVote::class, SurveyQuestion::class);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query
            ->where('is_active', true)
            ->where(fn (Builder $query): Builder => $query
                ->whereNull('closes_at')
                ->orWhereDate('closes_at', '>=', today()));
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $query->where(fn (Builder $query): Builder => $query
            ->where('is_active', false)
            ->orWhereDate('closes_at', '<', today()));
    }

    public function isOpen(): bool
    {
        return $this->is_active
            && ($this->closes_at === null || ! $this->closes_at->isBefore(today()));
    }

    public function statusLabel(): string
    {
        if ($this->isOpen()) {
            return 'Abierta';
        }

        return $this->is_active ? 'Finalizada' : 'Cerrada';
    }

    public function statusClasses(): string
    {
        return $this->isOpen()
            ? 'border-success/30 bg-success-soft text-success'
            : 'border-line bg-sunken text-ink-muted';
    }

    public function hasVotedBy(User $user): bool
    {
        return $this->relationLoaded('votes')
            ? $this->votes->contains('user_id', $user->id)
            : $this->votes()->where('user_id', $user->id)->exists();
    }

    protected function casts(): array
    {
        return [
            'closes_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
