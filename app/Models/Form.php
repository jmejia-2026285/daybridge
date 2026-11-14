<?php

namespace App\Models;

use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'area_id', 'title', 'description', 'closes_at', 'is_active'])]
class Form extends Model
{
    /** @use HasFactory<FormFactory> */
    use HasFactory;

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->latest();
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
            return 'Abierto';
        }

        return $this->is_active ? 'Finalizado' : 'Cerrado';
    }

    public function statusClasses(): string
    {
        return $this->isOpen()
            ? 'border-success/30 bg-success-soft text-success'
            : 'border-line bg-sunken text-ink-muted';
    }

    public function hasSubmittedBy(User $user): bool
    {
        return $this->relationLoaded('submissions')
            ? $this->submissions->contains('user_id', $user->id)
            : $this->submissions()->where('user_id', $user->id)->exists();
    }

    protected function casts(): array
    {
        return [
            'closes_at' => 'date',
            'is_active' => 'boolean',
        ];
    }
}
