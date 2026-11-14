<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'code',
    'type',
    'brand',
    'model',
    'serial',
    'client_name',
    'client_phone',
    'client_email',
    'issue',
    'diagnosis',
    'accessories',
    'notes',
    'status',
    'received_by',
    'received_at',
    'estimated_delivery_date',
])]
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    protected $table = 'equipment';

    protected static function booted(): void
    {
        static::created(function (Equipment $equipment): void {
            if (blank($equipment->code)) {
                $equipment->forceFill(['code' => sprintf('DSP-%04d', $equipment->id)])->saveQuietly();
            }
        });
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function latestTicket(): HasOne
    {
        return $this->hasOne(Ticket::class)->latestOfMany();
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $query->where('code', 'like', "%{$term}%")
                ->orWhere('serial', 'like', "%{$term}%")
                ->orWhere('client_name', 'like', "%{$term}%")
                ->orWhere('brand', 'like', "%{$term}%")
                ->orWhere('model', 'like', "%{$term}%");
        });
    }

    public function displayName(): string
    {
        return trim("{$this->type} {$this->brand} {$this->model}");
    }

    protected function casts(): array
    {
        return [
            'status' => ServiceStatus::class,
            'received_at' => 'datetime',
            'estimated_delivery_date' => 'date',
        ];
    }
}
