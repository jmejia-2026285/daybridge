<?php

namespace App\Enums;

enum TaskPriority: string
{
    case Low = 'BAJA';
    case Medium = 'MEDIA';
    case High = 'ALTA';

    public function label(): string
    {
        return match ($this) {
            self::Low => 'Baja',
            self::Medium => 'Media',
            self::High => 'Alta',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Low => 'border-line bg-sunken text-ink-muted',
            self::Medium => 'border-info/30 bg-info-soft text-info',
            self::High => 'border-danger/30 bg-danger-soft text-danger',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::Low => 'bg-ink-disabled',
            self::Medium => 'bg-info',
            self::High => 'bg-danger',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $priority): array => [$priority->value => $priority->label()])
            ->all();
    }
}
