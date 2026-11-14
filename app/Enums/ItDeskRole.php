<?php

namespace App\Enums;

enum ItDeskRole: string
{
    case Admin = 'admin';
    case Tecnico = 'tecnico';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador de IT Desk',
            self::Tecnico => 'Técnico',
        };
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
