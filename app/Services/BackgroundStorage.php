<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Guarda y elimina la imagen de fondo global de la aplicación.
 *
 * Mismo criterio de seguridad que AvatarStorage: la extensión nunca proviene
 * del cliente, se deduce del contenido real de la imagen, de modo que no es
 * posible subir .php, .html, .svg, etc.
 */
class BackgroundStorage
{
    public const MAX_BYTES = 5 * 1024 * 1024;

    private const DISK = 'public';

    private const DIRECTORY = 'background';

    /** @var array<int, string> */
    private const ALLOWED_TYPES = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    /**
     * Guarda la imagen y la deja activa como fondo de toda la aplicación.
     * Devuelve la ruta relativa (p. ej. «storage/background/abc.jpg»).
     *
     * @throws InvalidArgumentException cuando el contenido no es una imagen permitida.
     */
    public function storeFromDataUri(string $dataUri): string
    {
        $binary = $this->decode($dataUri);
        $extension = $this->detectExtension($binary);

        $filename = self::DIRECTORY.'/'.Str::uuid().'.'.$extension;
        Storage::disk(self::DISK)->put($filename, $binary);

        $this->deleteFile(Setting::get(Setting::BACKGROUND_KEY));

        $path = 'storage/'.$filename;
        Setting::set(Setting::BACKGROUND_KEY, $path);

        return $path;
    }

    /**
     * Quita la imagen activa y vuelve al fondo por omisión (las montañas).
     */
    public function remove(): void
    {
        $this->deleteFile(Setting::get(Setting::BACKGROUND_KEY));

        Setting::set(Setting::BACKGROUND_KEY, null);
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
            throw new InvalidArgumentException('Solo se permiten imágenes JPG, PNG o WEBP.');
        }

        return self::ALLOWED_TYPES[$info[2]];
    }

    private function deleteFile(?string $path): void
    {
        if ($path === null || $path === '' || $path === 'null') {
            return;
        }

        $file = Str::after($path, 'storage/');

        // Solo borra dentro de la carpeta del fondo (evita path traversal).
        if (! str_starts_with($file, self::DIRECTORY.'/') || str_contains($file, '..')) {
            return;
        }

        Storage::disk(self::DISK)->delete($file);
    }
}
