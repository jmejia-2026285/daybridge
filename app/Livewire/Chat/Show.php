<?php

namespace App\Livewire\Chat;

use App\Livewire\Chat\Concerns\ManagesChatThread;
use App\Models\Conversation;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

class Show extends Component
{
    use ManagesChatThread;
    use WithPagination;

    #[Locked]
    public Conversation $conversation;

    public function mount(Conversation $conversation): void
    {
        abort_unless($conversation->participants()->where('user_id', auth()->id())->exists(), 403);

        $this->conversation = $conversation;
        $this->conversation->markAsRead(auth()->user());
    }

    protected function chatThreadConversation(): Conversation
    {
        return $this->conversation;
    }

    public function render()
    {
        return view('livewire.chat.show');
    }
}
