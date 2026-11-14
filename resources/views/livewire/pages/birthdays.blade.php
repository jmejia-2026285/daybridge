<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Cumpleaños · Daybridge')] class extends Component
{
    #[On('birthday-wish-sent')]
    public function refreshCelebrations(): void
    {
        unset($this->celebrating, $this->upcoming, $this->upcomingByWeek);
    }

    #[Computed]
    public function celebrating(): Collection
    {
        return User::celebratingToday();
    }

    #[Computed]
    public function upcoming(): Collection
    {
        return User::upcomingBirthdays(60);
    }

    #[Computed]
    public function upcomingByWeek(): Collection
    {
        $thisWeek = today()->startOfWeek();
        $nextWeek = $thisWeek->copy()->addWeek();

        return $this->upcoming
            ->groupBy(fn (User $user): string => $user->nextBirthday()->copy()->startOfWeek()->toDateString())
            ->sortKeys()
            ->map(function (Collection $people, string $week) use ($thisWeek, $nextWeek): array {
                $start = Carbon::parse($week);

                $label = match (true) {
                    $start->isSameDay($thisWeek) => 'Esta semana',
                    $start->isSameDay($nextWeek) => 'Próxima semana',
                    default => 'Semana del '.$start->locale('es')->translatedFormat('j M'),
                };

                return ['label' => $label, 'people' => $people];
            })
            ->values();
    }

    #[Computed]
    public function weekday(): string
    {
        return today()->locale('es')->translatedFormat('l');
    }

    #[Computed]
    public function dateNumber(): string
    {
        return today()->format('d');
    }

    #[Computed]
    public function dateMonth(): string
    {
        return today()->locale('es')->translatedFormat('M');
    }
}; ?>

<div>
    <section class="mx-auto max-w-content px-4 pt-8 sm:px-6 md:px-10 md:pt-10">
        {{-- flex-wrap: en 390 px el título y la fecha no caben lado a lado y desbordaban la pantalla. --}}
        <div class="flex flex-wrap items-end justify-between gap-x-6 gap-y-3">
            <div class="min-w-0">
                <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">
                    {{ $this->weekday }}
                </p>

                <h1 class="mt-1 font-expanded font-display text-4xl font-black tracking-display sm:text-5xl">
                    Cumpleaños
                </h1>
            </div>

            <div class="max-w-[16rem] sm:text-right">
                <p class="font-condensed text-4xl font-black leading-none tabular-nums sm:text-5xl">
                    {{ $this->dateNumber }}<span class="text-accent">.</span>{{ $this->dateMonth }}
                </p>
                <p class="mt-2 text-xs text-ink-muted">
                    Celebraciones del equipo.
                </p>
            </div>
        </div>

        <x-dayline class="mt-8" />
    </section>

    <section class="mx-auto max-w-content px-4 pt-8 sm:px-6 md:px-10">
        <div class="flex items-center gap-3 border-t border-line pt-4">
            <span class="text-3xs font-bold tracking-label text-ink-muted uppercase">Hoy</span>
            <span class="h-px flex-1 bg-line"></span>

            @if ($this->celebrating->isNotEmpty())
                <span class="font-condensed text-xs tabular-nums text-ink-muted">
                    {{ $this->celebrating->count() }} {{ $this->celebrating->count() === 1 ? 'persona' : 'personas' }}
                </span>
            @endif
        </div>

        @if ($this->celebrating->isNotEmpty())
            <div class="mt-6 grid gap-4 lg:grid-cols-2">
                @foreach ($this->celebrating as $person)
                    <article class="border border-line bg-surface p-5 sm:p-6 apple-liquid-glass-light rounded-2xl" wire:key="today-{{ $person->id }}" style="background-color: rgba(15, 15, 15, 0.75); backdrop-filter: blur(16px); border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.1); color: #ffffff;">
                        <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
                            <x-avatar :user="$person" size="xl" />

                            <div class="min-w-0 flex-1">
                                <x-area-label :area="$person->area" />

                                <h2 class="mt-2 font-expanded font-display text-3xl font-black leading-none tracking-tight sm:text-4xl">
                                    {{ $person->name }}
                                </h2>

                                <p class="mt-1.5 text-sm text-ink-muted">{{ $person->job_title }}</p>

                                <div class="mt-5 flex items-end gap-7">
                                    <div>
                                        <p class="font-condensed text-3xl font-black leading-none tabular-nums">
                                            {{ $person->birth_date->age }}
                                        </p>
                                        <p class="mt-1 text-3xs tracking-label text-ink-muted uppercase">años</p>
                                    </div>

                                    <div>
                                        <p class="font-condensed text-3xl font-black leading-none tabular-nums">
                                            {{ $person->yearsAtCompany() }}
                                        </p>
                                        <p class="mt-1 text-3xs tracking-label text-ink-muted uppercase">en Daybridge</p>
                                    </div>
                                </div>

                                <div class="mt-5">
                                    <livewire:birthday-wish-button :recipient="$person" :key="'wish-birthdays-'.$person->id" />
                                </div>
                            </div>
                        </div>

                        @if ($person->birthdayWishesReceived->isNotEmpty())
                            <div class="mt-5 border-t border-line pt-3">
                                <p class="text-3xs tracking-label text-ink-muted uppercase">Ya le felicitaron</p>

                                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-2">
                                    @foreach ($person->birthdayWishesReceived->take(5) as $wish)
                                        <span class="inline-flex items-center gap-1.5 text-xs text-ink-secondary">
                                            <x-avatar :user="$wish->sender" size="2xs" />
                                            {{ Str::of($wish->sender->name)->explode(' ')->first() }}
                                        </span>
                                    @endforeach

                                    @if ($person->birthdayWishesReceived->count() > 5)
                                        <span class="text-xs text-ink-muted">
                                            +{{ $person->birthdayWishesReceived->count() - 5 }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </article>
                @endforeach
            </div>
        @else
            <div class="mt-6 border-y border-line py-14 text-center sm:py-16 apple-liquid-glass-light rounded-2xl" style="background-color: rgba(15, 15, 15, 0.75); backdrop-filter: blur(16px); border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.1); color: #ffffff;">
                <p class="font-expanded font-display text-3xl font-black tracking-display sm:text-4xl">
                    Hoy no hay velas que soplar.
                </p>

                <p class="mt-3 text-sm text-ink-muted">
                    @if ($this->upcoming->isNotEmpty())
                        El próximo cumpleaños es el de {{ $this->upcoming->first()->name }}, {{ $this->upcoming->first()->daysUntilBirthday() === 1 ? 'mañana' : 'en '.$this->upcoming->first()->daysUntilBirthday().' días' }}.
                    @else
                        No hay cumpleaños registrados en los próximos dos meses.
                    @endif
                </p>
            </div>
        @endif
    </section>

    <section class="mx-auto max-w-content px-4 pt-12 pb-16 sm:px-6 md:px-10">
        <div class="flex items-end justify-between border-b border-line pb-3">
            <h2 class="font-expanded font-display text-2xl font-bold tracking-tight">Próximos 60 días</h2>

            @if ($this->upcoming->isNotEmpty())
                <span class="font-condensed text-xs tabular-nums text-ink-muted">
                    {{ $this->upcoming->count() }} personas
                </span>
            @endif
        </div>

        @if ($this->upcomingByWeek->isNotEmpty())
            <div class="mt-8 overflow-x-auto pb-2">
                <div class="flex min-w-max gap-10">
                    @foreach ($this->upcomingByWeek as $week)
                        <div class="min-w-[14rem]">
                            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">
                                {{ $week['label'] }}
                            </p>

                            <div class="relative mt-5 border-t border-line">
                                <div class="flex gap-6 pt-5">
                                    @foreach ($week['people'] as $person)
                                        <div class="relative w-24 shrink-0">
                                            <span class="absolute -top-[1.4rem] left-0 h-1.5 w-1.5 rounded-full area-dot" style="--area-color: {{ $person->area->color }}"></span>

                                            <p class="font-condensed text-lg font-black leading-none tabular-nums">
                                                {{ $person->nextBirthday()->format('j') }}
                                                <span class="text-3xs font-medium tracking-label text-ink-muted uppercase">
                                                    {{ $person->nextBirthday()->locale('es')->translatedFormat('M') }}
                                                </span>
                                            </p>

                                            <p class="mt-1.5 truncate text-xs font-medium">{{ $person->name }}</p>

                                            <x-area-label :area="$person->area" class="mt-1" />

                                            <p class="mt-1 text-3xs text-ink-muted">
                                                {{ $person->daysUntilBirthday() === 1 ? 'Mañana' : 'En '.$person->daysUntilBirthday().' d' }}
                                            </p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="mt-6 border-y border-line py-12 text-center apple-liquid-glass-light rounded-2xl" style="background-color: rgba(15, 15, 15, 0.75); backdrop-filter: blur(16px); border-radius: 1rem; border: 1px solid rgba(255, 255, 255, 0.1); color: #ffffff;">
                <p class="text-sm text-ink-muted">No hay cumpleaños en los próximos dos meses.</p>
            </div>
        @endif
    </section>
</div>
