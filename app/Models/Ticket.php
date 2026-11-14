<?php

namespace App\Models;

use App\Enums\ServiceStatus;
use Database\Factories\TicketFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'equipment_id',
    'assigned_to',
    'folio',
    'description',
    'status',
    'opened_at',
    'completed_at',
    'printed_at',
])]
class Ticket extends Model
{
    /** @use HasFactory<TicketFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Ticket $ticket): void {
            if (blank($ticket->folio)) {
                $ticket->folio = static::nextFolio();
            }
        });
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function prints(): HasMany
    {
        return $this->hasMany(TicketPrint::class);
    }

    public function isPrinted(): bool
    {
        return $this->printed_at !== null;
    }

    public static function nextFolio(): string
    {
        $prefix = 'TCK-'.now()->year.'-';
        $last = static::query()
            ->where('folio', 'like', $prefix.'%')
            ->orderByDesc('folio')
            ->value('folio');

        $number = $last === null ? 1 : ((int) substr($last, strlen($prefix))) + 1;

        return $prefix.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }

    protected function casts(): array
    {
        return [
            'status' => ServiceStatus::class,
            'opened_at' => 'datetime',
            'completed_at' => 'datetime',
            'printed_at' => 'datetime',
        ];
    }
}
