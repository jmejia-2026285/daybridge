<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Editor = 'editor';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administración',
            self::Editor => 'Editor',
            self::User => 'Colaborador',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Gestiona personas, áreas y la configuración de la plataforma.',
            self::Editor => 'Publica comunicados y contenido para su área.',
            self::User => 'Consulta el contenido y participa en la comunidad.',
        };
    }

    public function canManageContent(): bool
    {
        return $this !== self::User;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role): array => [$role->value => $role->label()])
            ->all();
    }
}
