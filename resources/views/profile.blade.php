<x-app-layout>
    <div class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-12">
        <div class="border-b border-line pb-6">
            <p class="text-2xs font-semibold tracking-label text-ink-muted uppercase">Cuenta</p>
            <h1 class="mt-2 font-display text-3xl font-extrabold tracking-display sm:text-4xl">Tu perfil</h1>
            <p class="mt-2 max-w-prose text-base text-ink-secondary">
                Tus datos, tu acceso y las preferencias de esta sesión.
            </p>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-12">
            <aside class="lg:col-span-4">
                <div class="border border-line bg-surface p-6 lg:sticky lg:top-8">
                    <div class="flex flex-col items-center">
                        <livewire:profile-avatar />
                    </div>

                    <h2 class="mt-4 font-display text-2xl font-bold tracking-tight">{{ auth()->user()->name }}</h2>

                    <p class="mt-0.5 text-sm text-ink-muted">{{ auth()->user()->job_title ?? 'Sin puesto asignado' }}</p>

                    <x-area-label :area="auth()->user()->area" class="mt-2" />

                    <dl class="mt-5 space-y-3 border-t border-line pt-4 text-sm">
                        <div>
                            <dt class="text-2xs font-semibold tracking-label text-ink-muted uppercase">Correo electrónico</dt>
                            <dd class="mt-0.5 break-all">{{ auth()->user()->email }}</dd>
                        </div>

                        <div>
                            <dt class="text-2xs font-semibold tracking-label text-ink-muted uppercase">En Daybridge desde</dt>
                            <dd class="mt-0.5">
                                {{ (auth()->user()->joined_at ?? auth()->user()->created_at)?->locale('es')->translatedFormat('F \d\e Y') ?? 'Sin fecha registrada' }}
                            </dd>
                        </div>

                        @if (auth()->user()->birth_date)
                            <div>
                                <dt class="text-2xs font-semibold tracking-label text-ink-muted uppercase">Fecha de nacimiento</dt>
                                <dd class="mt-0.5">
                                    {{ auth()->user()->birth_date->locale('es')->translatedFormat('j \d\e F \d\e Y') }}
                                </dd>
                            </div>
                        @endif
                    </dl>
                </div>
            </aside>

            <div class="space-y-6 lg:col-span-8">
                <section class="border border-line bg-surface p-5 sm:p-6">
                    <livewire:profile.update-profile-information-form />
                </section>

                <section class="border border-line bg-surface p-5 sm:p-6">
                    <livewire:profile.update-password-form />
                </section>

                <section class="border border-line bg-surface p-5 sm:p-6">
                    <livewire:profile.delete-user-form />
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
