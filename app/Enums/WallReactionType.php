<?php

namespace App\Enums;

enum WallReactionType: string
{
    case Like = 'ME_GUSTA';
    case Clap = 'APLAUSO';
    case Celebrate = 'CELEBRACION';

    public function label(): string
    {
        return match ($this) {
            self::Like => 'Me gusta',
            self::Clap => 'Aplauso',
            self::Celebrate => 'Celebración',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Like => 'heart',
            self::Clap => 'star',
            self::Celebrate => 'cake',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
