<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] #[Title('Apariencia · Administración')] class extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }
}; ?>

@php
    $currentBackground = App\Models\Setting::get(App\Models\Setting::BACKGROUND_KEY);
    // URL absoluta: en rutas anidadas (/admin/…) la ruta relativa «storage/…» se resolvería mal.
    $backgroundUrl = $currentBackground ? asset($currentBackground) : null;
@endphp

<div
    class="mx-auto max-w-content px-4 py-8 sm:px-6 md:px-10 md:py-10"
    x-data="{
        backgroundPath: @js($backgroundUrl),
        uploading: false,
        status: '',
        statusType: '',

        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;
            event.target.value = '';

            if (!file.type.startsWith('image/')) {
                this.setStatus('Por favor selecciona una imagen válida', 'error');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.setStatus('La imagen es demasiado grande (máx 5 MB)', 'error');
                return;
            }

            this.uploading = true;
            this.status = 'Subiendo imagen...';
            this.statusType = 'info';

            try {
                const base64 = await this.toBase64(file);
                const response = await fetch('{{ route('admin.background.update') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                    body: JSON.stringify({ image_base64: base64 }),
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // El servidor devuelve ruta relativa; aquí se vuelve absoluta para que
                    // funcione igual en rutas raíz y anidadas, por localhost o por IP.
                    this.backgroundPath = location.origin + '/' + data.path;
                    this.setStatus('Fondo actualizado para toda la aplicación', 'success');
                } else {
                    throw new Error(data.message || 'Error al subir la imagen');
                }
            } catch (error) {
                this.setStatus(error.message || 'Error de conexión', 'error');
            } finally {
                this.uploading = false;
            }
        },

        async removeBackground() {
            if (!confirm('¿Estás seguro de que deseas volver al fondo por omisión?')) return;

            this.uploading = true;
            try {
                const response = await fetch('{{ route('admin.background.destroy') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                    },
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.backgroundPath = null;
                    this.setStatus('Se restauró el fondo por omisión', 'success');
                } else {
                    throw new Error(data.message || 'Error al eliminar la imagen');
                }
            } catch (error) {
                this.setStatus(error.message || 'Error de conexión', 'error');
            } finally {
                this.uploading = false;
            }
        },

        toBase64(file) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.readAsDataURL(file);
                reader.onload = () => resolve(reader.result);
                reader.onerror = () => reject(new Error('No se pudo leer la imagen'));
            });
        },

        setStatus(msg, type) {
            this.status = msg;
            this.statusType = type;
            setTimeout(() => { this.status = ''; }, 5000);
        },
    }"
>
    <header class="flex flex-wrap items-end justify-between gap-4 border-b border-line pb-5">
        <div>
            <p class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Administración</p>
            <h1 class="mt-1 font-expanded text-3xl font-black tracking-display sm:text-4xl">Apariencia</h1>
            <p class="mt-2 max-w-prose text-sm text-ink-muted">
                Imagen de fondo de toda la intranet: se ve igual en el escritorio y en el celular, para todas las personas.
            </p>
        </div>
    </header>

    <div
        x-cloak
        x-show="status"
        x-transition
        class="mt-4 border-l-2 px-3 py-2 text-sm"
        :class="statusType === 'error' ? 'border-danger bg-danger-soft text-danger' : (statusType === 'success' ? 'border-success bg-success-soft text-success' : 'border-brand bg-brand-soft text-brand-strong')"
        x-text="status"
        role="status"
    ></div>

    <div class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_320px]">
        {{-- Vista previa --}}
        <section class="border border-line bg-surface rounded-2xl p-5">
            <h2 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Vista previa</h2>

            <div class="relative mt-3 overflow-hidden rounded-xl border border-line" :class="uploading ? 'opacity-60' : ''">
                <template x-if="backgroundPath">
                    <img :src="backgroundPath" class="aspect-video w-full object-cover" alt="Fondo actual de Daybridge">
                </template>
                <template x-if="!backgroundPath">
                    <img src="{{ asset('media/background/fondo-montanas-desktop.jpg') }}" class="aspect-video w-full object-cover" alt="Fondo por omisión de Daybridge (montañas)">
                </template>
                <div class="absolute inset-0 flex items-end bg-gradient-to-t from-black/70 via-black/10 to-transparent p-4">
                    <p class="text-sm font-semibold text-white">
                        <span x-show="backgroundPath">Fondo personalizado</span>
                        <span x-show="!backgroundPath">Fondo por omisión (montañas)</span>
                    </p>
                </div>
            </div>

            <p class="mt-3 text-xs text-ink-muted">
                Se aplica detrás del velo oscuro de la interfaz, igual que el fondo actual. Si el cambio no se ve de inmediato, recarga la página.
            </p>
        </section>

        {{-- Controles --}}
        <section class="flex flex-col gap-3 border border-line bg-surface rounded-2xl p-5">
            <h2 class="text-3xs font-semibold tracking-label text-ink-muted uppercase">Cambiar fondo</h2>

            <label
                class="flex min-h-24 cursor-pointer flex-col items-center justify-center gap-1 rounded-xl border border-dashed border-line-strong bg-sunken px-4 py-6 text-center transition duration-fast ease-signal hover:border-brand"
                :class="uploading ? 'pointer-events-none opacity-60' : ''"
            >
                <x-icon name="upload" class="h-5 w-5 text-ink-muted" />
                <span class="text-sm font-semibold text-ink">Seleccionar imagen</span>
                <span class="text-xs text-ink-muted">JPG, PNG o WEBP · hasta 5 MB · preferible horizontal</span>
                <input type="file" class="hidden" accept="image/*" @change="handleFileUpload">
            </label>

            <button
                type="button"
                x-show="backgroundPath"
                @click="removeBackground"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md border border-line px-4 text-sm font-semibold text-ink transition duration-fast ease-signal hover:border-danger hover:text-danger"
            >
                <x-icon name="trash" class="h-4 w-4" />
                Volver al fondo por omisión
            </button>
        </section>
    </div>
</div>
