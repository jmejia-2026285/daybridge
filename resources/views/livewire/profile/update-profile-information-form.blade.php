<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;

new class extends Component
{
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public ?string $birth_date = null;

    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
        $this->phone = (string) Auth::user()->phone;
        $this->birth_date = Auth::user()->birth_date?->format('Y-m-d');
    }

    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\s()+\-0-9]{7,30}$/'],
            'birth_date' => ['nullable', 'date', 'before:today'],
        ], [
            'phone.regex' => 'El teléfono solo acepta dígitos, espacios y los símbolos ( ) + -.',
            'birth_date.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function sendVerification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('home', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section>
    <header>
        <h2 class="font-display text-lg font-bold tracking-tight">Información personal</h2>
        <p class="mt-1 text-sm text-ink-muted">Actualiza tu nombre, tu correo electrónico, tu teléfono y tu fecha de nacimiento.</p>
    </header>

    <form wire:submit="updateProfileInformation" class="mt-6 space-y-5">
        <div>
            <x-input-label for="name" value="Nombre" />
            <x-text-input wire:model="name" id="name" name="name" type="text" class="mt-1.5" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Correo electrónico" />
            <x-text-input wire:model="email" id="email" name="email" type="email" class="mt-1.5" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" />

            @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! auth()->user()->hasVerifiedEmail())
                <div class="mt-3 border border-line bg-warning-soft px-3 py-2.5 text-xs text-warning">
                    <p>
                        Tu correo todavía no está verificado.
                        <button wire:click.prevent="sendVerification" class="font-semibold underline">
                            Reenviar el enlace de verificación.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-1 font-medium">
                            Enviamos un nuevo enlace a tu correo.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div>
            <x-input-label for="phone" value="Teléfono" />
            <x-text-input wire:model="phone" id="phone" name="phone" type="tel" class="mt-1.5" autocomplete="tel" placeholder="+502 5555 5555" />
            <x-input-error :messages="$errors->get('phone')" />
        </div>

        <div>
            <x-input-label for="birth_date" value="Fecha de nacimiento" />
            <x-text-input wire:model="birth_date" id="birth_date" name="birth_date" type="date" class="mt-1.5" autocomplete="bday" max="{{ now()->subDay()->toDateString() }}" />
            <p class="mt-1 text-xs text-ink-muted">La usamos para avisarte y celebrar tu cumpleaños dentro de Daybridge.</p>
            <x-input-error :messages="$errors->get('birth_date')" />
        </div>

        <div class="flex items-center gap-4 pt-1">
            <x-primary-button>Guardar</x-primary-button>

            <x-action-message on="profile-updated">
                Guardado.
            </x-action-message>
        </div>
    </form>
</section>
