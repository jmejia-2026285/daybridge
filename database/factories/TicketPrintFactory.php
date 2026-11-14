<?php

namespace Database\Factories;

use App\Enums\TicketPrintType;
use App\Models\Ticket;
use App\Models\TicketPrint;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketPrint>
 */
class TicketPrintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'ticket_id' => Ticket::factory(),
            'type' => TicketPrintType::Equipment,
            'printed_by' => null,
            'printed_at' => now(),
        ];
    }

    public function client(): static
    {
        return $this->state(fn (): array => [
            'type' => TicketPrintType::Client,
        ]);
    }
}
