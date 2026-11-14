<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Pending = 'PENDIENTE';
    case InProgress = 'EN_PROCESO';
    case Completed = 'COMPLETADO';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::InProgress => 'En proceso',
            self::Completed => 'Completado',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'border-warning/30 bg-warning-soft text-warning',
            self::InProgress => 'border-info/30 bg-info-soft text-info',
            self::Completed => 'border-success/30 bg-success-soft text-success',
        };
    }

    public function dotClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-warning',
            self::InProgress => 'bg-info',
            self::Completed => 'bg-success',
        };
    }

    public function isCompleted(): bool
    {
        return $this === self::Completed;
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
