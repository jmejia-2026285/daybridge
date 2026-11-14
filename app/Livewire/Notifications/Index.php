<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Notificaciones · Daybridge')]
class Index extends Component
{
    use WithPagination;

    public string $filter = 'all'; // all, unread

    #[Computed]
    public function notifications(): LengthAwarePaginator
    {
        return Notification::query()
            ->forUser(auth()->user())
            ->recent()
            ->when($this->filter === 'unread', fn (Builder $q) => $q->unread())
            ->with(['subject', 'actor'])
            ->paginate(20);
    }

    #[Computed]
    public function unreadCount(): int
    {
        return auth()->user()->unreadNotifications()->count();
    }

    public function markAsRead(int $notificationId): void
    {
        $notification = Notification::query()
            ->forUser(auth()->user())
            ->findOrFail($notificationId);

        $notification->markAsRead();
        $this->dispatch('notification-read', notificationId: $notificationId);
    }

    public function markAllAsRead(): void
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        $this->dispatch('all-notifications-read');
    }

    public function render()
    {
        return view('livewire.notifications.index');
    }
}
