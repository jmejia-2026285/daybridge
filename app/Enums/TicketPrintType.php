<?php

namespace App\Enums;

enum TicketPrintType: string
{
    case Equipment = 'EQUIPO';
    case Client = 'CLIENTE';

    public function label(): string
    {
        return match ($this) {
            self::Equipment => 'Ticket de equipo',
            self::Client => 'Comprobante de cliente',
        };
    }
}
