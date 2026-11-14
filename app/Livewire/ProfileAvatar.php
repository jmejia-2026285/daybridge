<?php

namespace App\Livewire;

use App\Services\AvatarStorage;
use Illuminate\Contracts\View\View;
use InvalidArgumentException;
use Livewire\Component;
use Throwable;

/**
 * Avatar del perfil propio. Siempre opera sobre el usuario autenticado:
 * no existe ningún identificador de usuario controlable desde el cliente.
 */
class ProfileAvatar extends Component
{
    public ?string $avatarPath = null;

    public string $statusMessage = '';

    public function mount(): void
    {
        $this->avatarPath = auth()->user()->avatar;
    }

    public function updateAvatarBase64(AvatarStorage $avatars, string $base64String): void
    {
        $this->statusMessage = '';

        try {
            $filename = $avatars->storeFromDataUri(auth()->user(), $base64String);
            $this->avatarPath = 'storage/'.$filename;
            $this->statusMessage = 'Foto de perfil actualizada exitosamente.';
        } catch (InvalidArgumentException $e) {
            $this->statusMessage = 'Error al subir la foto: '.$e->getMessage();
        } catch (Throwable $e) {
            report($e);
            $this->statusMessage = 'Error al subir la foto.';
        }

        $this->dispatch('avatar-updated');
    }

    public function removeAvatar(AvatarStorage $avatars): void
    {
        try {
            $avatars->remove(auth()->user());
            $this->avatarPath = null;
            $this->statusMessage = 'Foto de perfil removida.';
        } catch (Throwable $e) {
            report($e);
            $this->statusMessage = 'Error al remover la foto.';
        }

        $this->dispatch('avatar-updated');
    }

    public function render(): View
    {
        return view('livewire.profile-avatar');
    }
}
