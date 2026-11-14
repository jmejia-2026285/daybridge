<div 
    class="flex flex-col items-center w-full"
    x-data="{ 
        avatarPath: '{{ $avatarPath }}',
        uploading: false,
        status: '',
        statusType: '',

        async handleFileUpload(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Client-side validation
            if (!file.type.startsWith('image/')) {
                this.setStatus('Por favor selecciona una imagen válida', 'error');
                return;
            }
            if (file.size > 5 * 1024 * 1024) {
                this.setStatus('La imagen es demasiado grande (máx 5MB)', 'error');
                return;
            }

            this.uploading = true;
            this.status = 'Subiendo imagen...';
            this.statusType = 'info';

            try {
                const base64 = await this.toBase64(file);
                
                const response = await fetch('{{ route('profile.avatar.update') }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content 
                    },
                    body: JSON.stringify({ image_base64: base64 })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.avatarPath = data.path;
                    this.setStatus('Foto de perfil actualizada', 'success');
                    // Notify Livewire to update its internal state
                    @this.set('avatarPath', data.path);
                } else {
                    throw new Error(data.message || 'Error al subir la imagen');
                }
            } catch (error) {
                console.error(error);
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
                reader.onerror = error => reject(error);
            });
        },

        async deleteAvatar() {
            if (!confirm('¿Estás seguro de que deseas eliminar tu foto de perfil?')) return;

            this.uploading = true;
            try {
                const response = await fetch('{{ route('profile.avatar.destroy') }}', {
                    method: 'POST',
                    headers: { 
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content 
                    },
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    this.avatarPath = null;
                    this.setStatus('Foto de perfil eliminada', 'success');
                    @this.set('avatarPath', null);
                } else {
                    throw new Error(data.message || 'Error al eliminar la imagen');
                }
            } catch (error) {
                this.setStatus(error.message || 'Error de conexión', 'error');
            } finally {
                this.uploading = false;
            }
        },

        setStatus(msg, type) {
            this.status = msg;
            this.statusType = type;
            setTimeout(() => { this.status = ''; }, 5000);
        }
    }"
>
    <!-- Contenedor del Avatar -->
    <div class="relative group w-24 h-24 mb-3">
        <!-- Imagen / Iniciales -->
        <div 
            class="w-24 h-24 rounded-full overflow-hidden border-2 border-line bg-surface transition-all duration-300 group-hover:border-accent shadow-sm"
            :class="uploading ? 'opacity-50 pointer-events-none' : ''"
        >
            <template x-if="avatarPath">
                <img 
                    :src="avatarPath" 
                    class="w-full h-full object-cover" 
                    alt="Foto de perfil"
                >
            </template>
            <template x-if="!avatarPath">
                <div class="w-full h-full flex items-center justify-center text-2xl font-bold text-ink-muted bg-surface">
                    {{ auth()->user()->initials() }}
                </div>
            </template>
        </div>

        <!-- Overlay de Carga -->
        <div
            x-cloak
            x-show="uploading"
            class="absolute inset-0 flex items-center justify-center bg-black/20 rounded-full z-10"
        >
            <x-icon name="spinner" class="h-6 w-6 text-white animate-spin" />
        </div>

        <!-- Overlay de Cambio (Trigger) -->
        <label 
            class="absolute inset-0 flex flex-col items-center justify-center bg-black/40 text-white rounded-full opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer z-20"
            title="Cambiar foto"
        >
            <x-icon name="camera" class="h-6 w-6 mb-0.5" />
            <span class="text-[10px] font-bold uppercase tracking-tight">Cambiar</span>
            <input 
                type="file" 
                class="hidden" 
                accept="image/*" 
                @change="handleFileUpload"
            >
        </label>

        <!-- Botón de Eliminar -->
        <template x-if="avatarPath">
            <button 
                @click="deleteAvatar"
                class="absolute -bottom-1 -right-1 flex items-center justify-center w-7 h-7 rounded-full bg-danger text-white shadow-md hover:bg-danger-strong transition-colors z-30"
                title="Eliminar foto"
            >
                <x-icon name="trash" class="h-3.5 w-3.5" />
            </button>
        </template>
    </div>

    <!-- Texto Descriptivo -->
    <p class="text-xs font-semibold text-ink-muted uppercase tracking-wider mb-2">
        Foto de perfil
    </p>

    <!-- Mensajes de Estado -->
    <div x-cloak
         x-show="status"
         x-transition
         class="text-[11px] font-medium px-3 py-1 rounded-full"
         :class="statusType === 'error' ? 'bg-danger-soft text-danger' : (statusType === 'success' ? 'bg-success-soft text-success' : 'bg-brand-soft text-brand')"
         x-text="status"
    ></div>
</div>
