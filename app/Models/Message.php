<?php

namespace App\Models;

use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['conversation_id', 'sender_id', 'body', 'type', 'metadata', 'read_at'])]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function replyTo(): ?self
    {
        $replyToId = $this->metadata['reply_to'] ?? null;

        if ($replyToId === null) {
            return null;
        }

        return $this->conversation->messages()->find($replyToId);
    }

    public function isFromCurrentUser(): bool
    {
        return $this->sender_id === auth()->id();
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
