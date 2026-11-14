<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Guarda y elimina avatares de forma segura.
 *
 * La extensión nunca proviene del cliente: se deduce del contenido real
 * de la imagen, de modo que no es posible subir .php, .html, .svg, etc.
 */
class AvatarStorage
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const DISK = 'public';

    private const DIRECTORY = 'avatars';

    /** @var array<int, string> */
    private const ALLOWED_TYPES = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
        IMAGETYPE_GIF => 'gif',
    ];

    /**
     * @throws InvalidArgumentException cuando el contenido no es una imagen permitida.
     */
    public function storeFromDataUri(User $user, string $dataUri): string
    {
        $binary = $this->decode($dataUri);
        $extension = $this->detectExtension($binary);

        $filename = self::DIRECTORY.'/'.$user->id.'-'.Str::uuid().'.'.$extension;
        Storage::disk(self::DISK)->put($filename, $binary);

        $this->deleteFile($user->avatar);

        $user->forceFill(['avatar' => 'storage/'.$filename])->save();

        return $filename;
    }

    public function remove(User $user): void
    {
        $this->deleteFile($user->avatar);

        $user->forceFill(['avatar' => null])->save();
    }

    private function decode(string $dataUri): string
    {
        if (! preg_match('#^data:image/[a-z0-9.+-]+;base64,#i', $dataUri, $header)) {
            throw new InvalidArgumentException('Formato de imagen no válido.');
        }

        // base64 ocupa ~4/3 del tamaño binario.
        if (strlen($dataUri) > (int) ceil(self::MAX_BYTES * 4 / 3) + strlen($header[0])) {
            throw new InvalidArgumentException('La imagen supera el tamaño máximo de 5 MB.');
        }

        $binary = base64_decode(substr($dataUri, strlen($header[0])), true);

        if ($binary === false || $binary === '') {
            throw new InvalidArgumentException('Datos de imagen no válidos.');
        }

        return $binary;
    }

    private function detectExtension(string $binary): string
    {
        $info = @getimagesizefromstring($binary);

        if ($info === false || ! isset(self::ALLOWED_TYPES[$info[2]])) {
            throw new InvalidArgumentException('Solo se permiten imágenes JPG, PNG, WEBP o GIF.');
        }

        return self::ALLOWED_TYPES[$info[2]];
    }

    private function deleteFile(?string $avatar): void
    {
        if ($avatar === null || $avatar === '' || $avatar === 'null') {
            return;
        }

        $path = Str::after($avatar, 'storage/');

        // Solo borra dentro de la carpeta de avatares (evita path traversal).
        if (! str_starts_with($path, self::DIRECTORY.'/') || str_contains($path, '..')) {
            return;
        }

        Storage::disk(self::DISK)->delete($path);
    }
}
