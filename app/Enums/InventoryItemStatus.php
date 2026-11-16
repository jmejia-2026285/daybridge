<?php

namespace App\Enums;

enum InventoryItemStatus: string
{
    case InStock = 'EN_CAJA';
    case Damaged = 'AVERIADO';
    case Warehouse = 'BODEGA';
    case InUse = 'EN_USO';
    case Retired = 'RETIRADO';

    public function label(): string
    {
        return match ($this) {
            self::InStock => 'En caja (nuevo)',
            self::Damaged => 'Dañado',
            self::Warehouse => 'En bodega',
            self::InUse => 'En uso',
            self::Retired => 'Retirado',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::InStock => 'border-success/30 bg-success-soft text-success',
            self::Damaged => 'border-warning/30 bg-warning-soft text-warning',
            self::Warehouse => 'border-info/30 bg-info-soft text-info',
            self::InUse => 'border-accent/30 bg-accent-soft text-accent-strong',
            self::Retired => 'border-danger/30 bg-danger-soft text-danger',
        };
    }
}
