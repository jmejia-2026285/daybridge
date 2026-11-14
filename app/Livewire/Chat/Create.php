<?php

namespace App\Livewire\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Support\ActionThrottle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class Create extends Component
{
    public string $search = '';

    #[Computed]
    public function users(): Collection
    {
        return User::query()
            ->active()
            ->where('id', '!=', auth()->id())
            ->with('area')
            ->when($this->search, fn (Builder $q) => $q->where('name', 'like', "%{$this->search}%"))
            ->orderBy('name')
            ->get();
    }

    public function startConversation(int $userId)
    {
        $recipient = User::query()->active()->findOrFail($userId);

        abort_if($recipient->is(auth()->user()), 422, 'No puedes iniciar un chat contigo mismo.');

        ActionThrottle::hit('chat-start', 10);

        $conversation = Conversation::startDirect(auth()->user(), $recipient);

        return redirect()->route('chat.show', $conversation);
    }

    public function render()
    {
        return view('livewire.chat.create');
    }
}
