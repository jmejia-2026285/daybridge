<?php

namespace App\Models;

use Carbon\Carbon;
use Database\Factories\AppointmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'technician_id',
    'title',
    'description',
    'scheduled_date',
    'scheduled_time',
    'completed',
])]
class Appointment extends Model
{
    /** @use HasFactory<AppointmentFactory> */
    use HasFactory;

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function timeLabel(): string
    {
        return Carbon::parse($this->scheduled_time)->format('H:i');
    }

    protected function casts(): array
    {
        return [
            'scheduled_date' => 'date',
            'completed' => 'boolean',
        ];
    }
}
