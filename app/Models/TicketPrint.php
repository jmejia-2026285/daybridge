<?php

namespace App\Models;

use App\Enums\TicketPrintType;
use Database\Factories\TicketPrintFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['ticket_id', 'type', 'printed_by', 'printed_at'])]
class TicketPrint extends Model
{
    /** @use HasFactory<TicketPrintFactory> */
    use HasFactory;

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function printedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'printed_by');
    }

    protected function casts(): array
    {
        return [
            'type' => TicketPrintType::class,
            'printed_at' => 'datetime',
        ];
    }
}
