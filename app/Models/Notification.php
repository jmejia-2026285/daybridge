<?php

namespace App\Models;

use Database\Factories\NotificationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['type', 'notifiable_type', 'notifiable_id', 'subject_type', 'subject_id', 'actor_id', 'data', 'read_at'])]
class Notification extends Model
{
    /** @use HasFactory<NotificationFactory> */
    use HasFactory;

    protected $table = 'notifications';

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if (! $this->read_at) {
            $this->update(['read_at' => now()]);
        }
    }

    public function getIcon(): string
    {
        return match ($this->type) {
            'mention' => 'at',
            'reply' => 'reply',
            'reaction' => 'heart',
            'wall_reaction' => 'heart',
            'wall_comment' => 'chat',
            'birthday_wish' => 'cake',
            'document_shared' => 'folder',
            'form_submitted' => 'clipboard',
            'survey_invitation' => 'poll',
            'task_assigned' => 'task',
            'task_completed' => 'check',
            'it_desk_ticket' => 'ticket',
            'system' => 'info',
            default => 'bell',
        };
    }

    public function getLabel(): string
    {
        return match ($this->type) {
            'mention' => 'Te mencionaron',
            'reply' => 'Respondieron a tu mensaje',
            'reaction' => 'Reaccionaron a tu publicación',
            'wall_reaction' => 'Reaccionaron en el muro',
            'wall_comment' => 'Comentaron en el muro',
            'birthday_wish' => 'Te desearon feliz cumpleaños',
            'document_shared' => 'Compartieron un documento',
            'form_submitted' => 'Enviaron un formulario',
            'survey_invitation' => 'Te invitaron a una encuesta',
            'task_assigned' => 'Te asignaron una tarea',
            'task_completed' => 'Completaron una tarea',
            'it_desk_ticket' => 'Nuevo ticket IT',
            'system' => 'Notificación del sistema',
            default => 'Nueva notificación',
        };
    }

    public function getActionUrl(): ?string
    {
        if ($this->type === 'birthday_wish') {
            return route('birthdays');
        }

        return $this->subject !== null && method_exists($this->subject, 'getUrl')
            ? $this->subject->getUrl()
            : null;
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('notifiable_type', User::class)
            ->where('notifiable_id', $user->id);
    }

    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
