<?php

namespace App\Livewire\Chat\Concerns;

use App\Models\Conversation;
use App\Models\Message;
use App\Support\ActionThrottle;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;

trait ManagesChatThread
{
    public string $message = '';

    public int $replyingToId = 0;

    abstract protected function chatThreadConversation(): ?Conversation;

    #[Computed]
    public function chatMessages(): LengthAwarePaginator
    {
        $conversation = $this->chatThreadConversation();

        if ($conversation === null) {
            return new LengthAwarePaginator([], 0, 50);
        }

        // La relación messages() ordena por created_at ASC; reorder anula esa cláusula
        // y garantiza orden cronológico inverso (las 50 más recientes), que la vista
        // vuelve a invertir para mostrar lo más nuevo abajo.
        return $conversation->messages()
            ->with(['sender.area'])
            ->reorder('id', 'desc')
            ->paginate(50, ['*'], 'messages-page');
    }

    public function sendMessage(): void
    {
        $conversation = $this->chatThreadConversation();

        if ($conversation === null) {
            return;
        }

        // Re-autoriza en cada envío: la membresía pudo cambiar desde mount().
        abort_unless(auth()->user()->can('view', $conversation), 403);

        $this->validate([
            'message' => ['required', 'string', 'max:5000'],
        ]);

        ActionThrottle::hit('chat-send', 30, errorField: 'message');

        $metadata = null;

        if ($this->replyingToId !== 0) {
            $quotedMessage = $conversation->messages()->find($this->replyingToId);

            if ($quotedMessage !== null) {
                $metadata = ['reply_to' => $quotedMessage->id];
            }
        }

        $sent = $conversation->messages()->create([
            'sender_id' => auth()->id(),
            'body' => $this->message,
            'type' => 'text',
            'metadata' => $metadata,
        ]);

        $conversation->update(['last_message_at' => now()]);

        $this->message = '';
        $this->replyingToId = 0;

        unset($this->chatMessages);

        $this->dispatch('message-sent', messageId: $sent->id);
    }

    public function deleteMessage(int $messageId): void
    {
        $message = Message::query()->findOrFail($messageId);

        abort_unless(auth()->user()->can('delete', $message), 403);

        $conversation = $this->chatThreadConversation();

        if ($conversation === null || $message->conversation->isNot($conversation)) {
            return;
        }

        $message->delete();

        $lastMessage = $conversation->messages()->reorder()->orderByDesc('id')->first();

        $conversation->update(['last_message_at' => $lastMessage?->created_at]);

        if ($this->replyingToId === $messageId) {
            $this->cancelReply();
        }

        unset($this->chatMessages);
    }

    public function startReply(int $messageId): void
    {
        $this->replyingToId = $messageId;
    }

    public function cancelReply(): void
    {
        $this->replyingToId = 0;
    }
}
