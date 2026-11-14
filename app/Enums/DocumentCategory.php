<?php

namespace App\Enums;

enum DocumentCategory: string
{
    case Policy = 'POLITICA';
    case Manual = 'MANUAL';
    case Template = 'FORMATO';
    case Minutes = 'ACTA';
    case Other = 'OTRO';

    public function label(): string
    {
        return match ($this) {
            self::Policy => 'Política',
            self::Manual => 'Manual',
            self::Template => 'Formato',
            self::Minutes => 'Acta',
            self::Other => 'Otro',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $category): array => [$category->value => $category->label()])
            ->all();
    }
}
