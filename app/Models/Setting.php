<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Par de clave/valor para la configuración global de la aplicación
 * (por ejemplo, la imagen de fondo de toda la intranet).
 *
 * @property string $key
 * @property string|null $value
 */
class Setting extends Model
{
    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    /**
     * Clave de la imagen de fondo global de la aplicación.
     */
    public const BACKGROUND_KEY = 'app.background';

    /**
     * Valor de una configuración o el valor por omisión si no existe.
     */
    public static function get(string $key, ?string $default = null): ?string
    {
        return static::query()->find($key)?->value ?? $default;
    }

    /**
     * Guarda un valor. `null` elimina la clave.
     */
    public static function set(string $key, ?string $value): void
    {
        if ($value === null) {
            static::query()->where('key', $key)->delete();
        } else {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
