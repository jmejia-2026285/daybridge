<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['subject', 'creator_id', 'is_group', 'last_message_at'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function participants(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'conversation_participants')
            ->withPivot('last_read_at', 'is_admin')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function unreadCountFor(User $user): int
    {
        $participant = $this->participants()->where('user_id', $user->id)->first();

        if (! $participant || ! $participant->pivot->last_read_at) {
            return $this->messages()->count();
        }

        return $this->messages()
            ->where('created_at', '>', $participant->pivot->last_read_at)
            ->where('sender_id', '!=', $user->id)
            ->count();
    }

    public function markAsRead(User $user): void
    {
        $this->participants()->updateExistingPivot($user->id, [
            'last_read_at' => now(),
        ]);
    }

    public function otherParticipants(User $user): BelongsToMany
    {
        return $this->participants()->where('user_id', '!=', $user->id);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->whereHas('participants', fn (Builder $q) => $q->where('user_id', $user->id));
    }

    public static function startDirect(User $initiator, User $recipient): self
    {
        $existing = static::query()
            ->where('is_group', false)
            ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $initiator->id))
            ->whereHas('participants', fn (Builder $q) => $q->where('user_id', $recipient->id))
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $conversation = static::query()->create([
            'creator_id' => $initiator->id,
            'is_group' => false,
            'last_message_at' => now(),
        ]);

        $conversation->participants()->attach([
            $initiator->id => ['is_admin' => true, 'last_read_at' => now()],
            $recipient->id => ['is_admin' => false, 'last_read_at' => null],
        ]);

        return $conversation;
    }

    public function getDisplayName(User $user): string
    {
        if ($this->is_group && $this->subject) {
            return $this->subject;
        }

        $other = $this->otherParticipants($user)->first();

        return $other?->name ?? 'Conversación';
    }

    public function getDisplayAvatar(User $user): ?string
    {
        if ($this->is_group) {
            return null;
        }

        $other = $this->otherParticipants($user)->first();

        return $other?->avatar;
    }
}
