<?php

use App\Models\BirthdayWish;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Volt\Component;

new class extends Component
{
    public User $recipient;

    public function send(): void
    {
        if ($this->recipient->is(Auth::user())) {
            return;
        }

        $wish = BirthdayWish::query()->firstOrCreate([
            'sender_id' => Auth::id(),
            'recipient_id' => $this->recipient->id,
            'year' => today()->year,
        ], [
            'message' => '¡Feliz cumpleaños!',
        ]);

        if ($wish->wasRecentlyCreated) {
            Notification::query()->create([
                'type' => 'birthday_wish',
                'notifiable_type' => User::class,
                'notifiable_id' => $this->recipient->id,
                'subject_type' => BirthdayWish::class,
                'subject_id' => $wish->id,
                'actor_id' => Auth::id(),
                'data' => ['message' => 'Regresale un mensaje al equipo celebrando tu día.'],
            ]);
        }

        unset($this->sent, $this->wishesCount);

        $this->dispatch('birthday-wish-sent');
    }

    #[Computed]
    public function sent(): bool
    {
        return BirthdayWish::query()
            ->where('sender_id', Auth::id())
            ->where('recipient_id', $this->recipient->id)
            ->where('year', today()->year)
            ->exists();
    }

    #[Computed]
    public function wishesCount(): int
    {
        return $this->recipient->birthdayWishesReceived()
            ->where('year', today()->year)
            ->count();
    }
}; ?>

<div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
    @if ($recipient->is(auth()->user()))
        <span class="text-3xs font-medium tracking-label text-ink-muted uppercase">
            {{ $this->wishesCount }} {{ $this->wishesCount === 1 ? 'felicitación' : 'felicitaciones' }}
        </span>
    @elseif ($this->sent)
        <span class="inline-flex items-center gap-1.5 text-3xs font-semibold tracking-label text-ink-muted uppercase">
            <x-icon name="check" class="h-3 w-3 text-success" />
            Felicitado
        </span>
    @else
        <button
            type="button"
            wire:click="send"
            wire:loading.attr="disabled"
            wire:target="send"
            class="inline-flex items-center rounded-xs bg-accent px-3 py-1.5 text-3xs font-bold tracking-label text-accent-foreground uppercase transition duration-fast ease-signal hover:bg-accent/90 disabled:cursor-wait disabled:opacity-60"
        >
            Felicitar
        </button>
    @endif

    @if (! $recipient->is(auth()->user()) && $this->wishesCount > 0)
        <span class="font-condensed text-xs tabular-nums text-ink-muted">
            {{ $this->wishesCount }} {{ $this->wishesCount === 1 ? 'felicitación' : 'felicitaciones' }}
        </span>
    @endif
</div>
