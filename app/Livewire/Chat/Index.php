<?php

namespace App\Livewire\Chat;

use App\Livewire\Chat\Concerns\ManagesChatThread;
use App\Models\Conversation;
use App\Models\User;
use App\Support\ActionThrottle;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use ManagesChatThread;
    use WithPagination;

    public string $search = '';

    public bool $newChatOpen = false;

    public bool $showNewChatModal = false;

    public ?int $selectedConversationId = null;

    #[Computed]
    public function conversations(): LengthAwarePaginator
    {
        return Conversation::query()
            ->forUser(auth()->user())
            ->with([
                'participants.area',
                'latestMessage.sender.area',
            ])
            ->when($this->search, function (Builder $query): void {
                // Agrupado: sin el where() el orWhere escapaba del filtro forUser()
                // y mostraba conversaciones ajenas.
                $query->where(function (Builder $q): void {
                    $q->whereHas('participants', fn (Builder $p) => $p->where('name', 'like', "%{$this->search}%"))
                        ->orWhere('subject', 'like', "%{$this->search}%");
                });
            })
            ->withCount(['messages as unread_count' => function (Builder $query) {
                $query->where('sender_id', '!=', auth()->id())
                    ->where(function (Builder $q) use ($query) {
                        $q->whereNull('read_at')
                            ->orWhereHas('conversation.participants', fn (Builder $p) => $p
                                ->where('user_id', auth()->id())
                                ->where('last_read_at', '<', $query->getModel()->getConnection()->raw('messages.created_at'))
                            );
                    });
            }])
            ->orderByDesc('last_message_at')
            ->paginate(20);
    }

    #[Computed]
    public function selectedConversation(): ?Conversation
    {
        if ($this->selectedConversationId === null) {
            return null;
        }

        return Conversation::query()
            ->forUser(auth()->user())
            ->with(['participants.area', 'latestMessage.sender.area'])
            ->find($this->selectedConversationId);
    }

    #[Computed]
    public function availableUsers(): Collection
    {
        return User::query()
            ->active()
            ->where('id', '!=', auth()->id())
            ->with('area')
            ->orderBy('name')
            ->get();
    }

    protected function chatThreadConversation(): ?Conversation
    {
        return $this->selectedConversation;
    }

    public function getUsersProperty(): Collection
    {
        return $this->availableUsers;
    }

    /**
     * Abre una conversación existente dentro del panel derecho, sin salir de la página.
     */
    public function openConversation(int $conversationId): void
    {
        unset($this->selectedConversation);

        $this->selectedConversationId = $conversationId;

        $this->selectedConversation?->markAsRead(auth()->user());

        unset($this->chatMessages);
    }

    /**
     * Vuelve del hilo a la lista (botón atrás en móvil), sin recargar la página.
     */
    public function closeConversation(): void
    {
        $this->selectedConversationId = null;

        unset($this->selectedConversation);
        unset($this->chatMessages);
    }

    public function startConversation(int $userId): void
    {
        $recipient = User::query()->active()->findOrFail($userId);

        abort_if($recipient->is(auth()->user()), 422, 'No puedes iniciar un chat contigo mismo.');

        ActionThrottle::hit('chat-start', 10);

        $conversation = Conversation::startDirect(auth()->user(), $recipient);

        $this->newChatOpen = false;
        $this->showNewChatModal = false;

        unset($this->conversations);

        $this->openConversation($conversation->id);
    }

    public function selectUser(int $userId): void
    {
        $this->startConversation($userId);
    }

    public function openNewChat(): void
    {
        $this->newChatOpen = true;
        $this->showNewChatModal = true;
    }

    public function closeNewChat(): void
    {
        $this->newChatOpen = false;
        $this->showNewChatModal = false;
    }

    public function clearSearch(): void
    {
        $this->search = '';
    }

    public function render(): View
    {
        // Al re-renderizar (incluido el poll de 5 s) mantenemos la conversación activa como leída.
        $this->selectedConversation()?->markAsRead(auth()->user());

        return view('livewire.chat.index');
    }
}
