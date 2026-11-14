<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Enums\TicketPrintType;
use App\Models\Equipment;
use App\Models\Ticket;
use App\Models\TicketPrint;
use App\Models\User;
use App\Services\ItDesk\TicketService;
use App\Support\Code128;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ItDeskTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_users_cannot_open_it_desk(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('it-desk.dashboard'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/')
            ->assertDontSee('href="'.route('it-desk.dashboard').'"', false);
    }

    public function test_technicians_and_admins_can_open_it_desk(): void
    {
        $technician = User::factory()->technician()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($technician)
            ->get(route('it-desk.dashboard'))
            ->assertOk()
            ->assertSee('IT Desk');

        $this->actingAs($technician)
            ->get('/')
            ->assertSee(route('it-desk.dashboard'));

        $this->actingAs($admin)
            ->get(route('it-desk.dashboard'))
            ->assertOk();
    }

    public function test_registering_equipment_generates_the_code_and_first_ticket(): void
    {
        $technician = User::factory()->technician()->create();

        $this->actingAs($technician);

        Volt::test('pages.it-desk.equipment.create')
            ->set('form.type', 'Laptop')
            ->set('form.brand', 'Dell')
            ->set('form.model', 'Latitude 5440')
            ->set('form.serial', 'SN-1234')
            ->set('form.client_name', 'Mariana Ordóñez')
            ->set('form.client_phone', '5555-1201')
            ->set('form.client_email', 'mariana@daybridge.test')
            ->set('form.issue', 'No enciende.')
            ->set('form.accessories', 'Cargador')
            ->set('form.received_by', $technician->id)
            ->call('save')
            ->assertHasNoErrors();

        $equipment = Equipment::query()->sole();

        $this->assertSame('DSP-0001', $equipment->code);
        $this->assertSame(ServiceStatus::Pending, $equipment->status);
        $this->assertSame('No enciende.', $equipment->issue);

        $ticket = $equipment->tickets()->sole();

        $this->assertSame('TCK-'.now()->year.'-0001', $ticket->folio);
        $this->assertSame(ServiceStatus::Pending, $ticket->status);
        $this->assertTrue($ticket->assignedTo->is($technician));
    }

    public function test_equipment_form_rejects_values_over_the_reference_limits(): void
    {
        $technician = User::factory()->technician()->create();

        $this->actingAs($technician);

        Volt::test('pages.it-desk.equipment.create')
            ->set('form.type', str_repeat('a', 61))
            ->set('form.brand', str_repeat('b', 61))
            ->set('form.model', str_repeat('c', 81))
            ->set('form.serial', str_repeat('d', 81))
            ->set('form.client_name', str_repeat('e', 101))
            ->set('form.client_phone', '123')
            ->set('form.client_email', str_repeat('f', 113).'@example.com')
            ->set('form.issue', str_repeat('g', 256))
            ->set('form.diagnosis', str_repeat('h', 1001))
            ->set('form.accessories', str_repeat('i', 501))
            ->set('form.notes', str_repeat('j', 501))
            ->set('form.received_by', $technician->id)
            ->set('form.estimated_delivery_date', today()->subDay()->toDateString())
            ->call('save')
            ->assertHasErrors([
                'form.type',
                'form.brand',
                'form.model',
                'form.serial',
                'form.client_name',
                'form.client_phone',
                'form.client_email',
                'form.issue',
                'form.diagnosis',
                'form.accessories',
                'form.notes',
                'form.estimated_delivery_date',
            ]);

        $this->assertDatabaseCount('equipment', 0);
    }

    public function test_ticket_folios_are_sequential_per_year(): void
    {
        $first = Ticket::factory()->create();
        $second = Ticket::factory()->create();

        $prefix = 'TCK-'.now()->year.'-';

        $this->assertSame($prefix.'0001', $first->folio);
        $this->assertSame($prefix.'0002', $second->folio);
    }

    public function test_a_ticket_cannot_change_to_its_current_status(): void
    {
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();

        $this->expectException(ValidationException::class);

        app(TicketService::class)->changeStatus($ticket, $ticket->status);
    }

    public function test_completing_a_ticket_sets_the_completion_date_and_clears_it_when_reopened(): void
    {
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();
        $service = app(TicketService::class);

        $service->changeStatus($ticket, ServiceStatus::Completed);

        $this->assertNotNull($ticket->fresh()->completed_at);

        $service->changeStatus($ticket->fresh(), ServiceStatus::InProgress);

        $this->assertNull($ticket->fresh()->completed_at);
    }

    public function test_equipment_status_follows_its_open_tickets(): void
    {
        $equipment = Equipment::factory()->create();
        $service = app(TicketService::class);

        $pending = Ticket::factory()->create(['equipment_id' => $equipment->id]);
        $other = Ticket::factory()->create(['equipment_id' => $equipment->id]);

        $service->changeStatus($pending, ServiceStatus::Completed);
        $service->changeStatus($other, ServiceStatus::Completed);

        $this->assertSame(ServiceStatus::Completed, $equipment->fresh()->status);

        $service->changeStatus($other->fresh(), ServiceStatus::Pending);

        $this->assertSame(ServiceStatus::Pending, $equipment->fresh()->status);

        $service->changeStatus($other->fresh(), ServiceStatus::InProgress);

        $this->assertSame(ServiceStatus::InProgress, $equipment->fresh()->status);
    }

    public function test_printing_a_ticket_logs_both_copies_and_marks_the_ticket(): void
    {
        $technician = User::factory()->technician()->create();
        $ticket = Ticket::factory()->create();

        app(TicketService::class)->markPrinted($ticket, $technician);

        $this->assertNotNull($ticket->fresh()->printed_at);
        $this->assertSame(2, TicketPrint::query()->where('ticket_id', $ticket->id)->count());
        $this->assertSame(
            [TicketPrintType::Equipment->value, TicketPrintType::Client->value],
            TicketPrint::query()->where('ticket_id', $ticket->id)->orderBy('id')->pluck('type')->map->value->all(),
        );
    }

    public function test_technicians_can_update_a_ticket_from_the_equipment_detail(): void
    {
        $technician = User::factory()->technician()->create();
        $equipment = Equipment::factory()->create();
        $ticket = Ticket::factory()->create(['equipment_id' => $equipment->id]);

        $this->actingAs($technician);

        Volt::test('pages.it-desk.equipment.show', ['equipment' => $equipment])
            ->call('changeStatus', $ticket->id, ServiceStatus::InProgress->value)
            ->assertHasNoErrors();

        $this->assertSame(ServiceStatus::InProgress, $ticket->fresh()->status);
        $this->assertSame(ServiceStatus::InProgress, $equipment->fresh()->status);
    }

    public function test_technicians_can_open_the_equipment_pages(): void
    {
        $technician = User::factory()->technician()->create();
        $equipment = Equipment::factory()->create(['client_name' => 'Cliente Visible']);

        $this->actingAs($technician)
            ->get(route('it-desk.equipment.index'))
            ->assertOk()
            ->assertSee('Cliente Visible');

        $this->actingAs($technician)
            ->get(route('it-desk.equipment.show', $equipment))
            ->assertOk()
            ->assertSee('Cliente Visible')
            ->assertSee('Abrir otro ticket');

        $this->actingAs($technician)
            ->get(route('it-desk.equipment.edit', $equipment))
            ->assertOk()
            ->assertSee('Editar equipo');
    }

    public function test_only_it_desk_admins_can_delete_equipment(): void
    {
        $technician = User::factory()->technician()->create();
        $itDeskAdmin = User::factory()->itDeskAdmin()->create();
        $equipment = Equipment::factory()->create();

        $this->assertFalse($technician->can('delete', $equipment));
        $this->assertTrue($itDeskAdmin->can('delete', $equipment));
        $this->assertTrue(User::factory()->admin()->create()->can('delete', $equipment));
    }

    public function test_the_print_page_shows_both_ticket_copies(): void
    {
        $technician = User::factory()->technician()->create();
        $equipment = Equipment::factory()->create(['client_name' => 'Cliente Impreso']);
        $ticket = Ticket::factory()->create(['equipment_id' => $equipment->id]);

        $this->actingAs($technician)
            ->get(route('it-desk.equipment.print', [$equipment, $ticket]))
            ->assertOk()
            ->assertSee('Ticket de equipo')
            ->assertSee('Comprobante de ingreso')
            ->assertSee($equipment->code)
            ->assertSee($ticket->folio)
            ->assertSee('Cliente Impreso')
            ->assertSee('Código de barras', false);
    }

    public function test_printing_from_the_page_logs_both_copies_and_marks_the_ticket(): void
    {
        $technician = User::factory()->technician()->create();
        $equipment = Equipment::factory()->create();
        $ticket = Ticket::factory()->create(['equipment_id' => $equipment->id]);

        $this->actingAs($technician);

        Volt::test('pages.it-desk.equipment.print', ['equipment' => $equipment, 'ticket' => $ticket])
            ->call('print')
            ->assertHasNoErrors()
            ->assertDispatched('print-ticket')
            ->assertSet('printed', true);

        $ticket->refresh();

        $this->assertNotNull($ticket->printed_at);
        $this->assertSame(2, $ticket->prints()->count());
        $this->assertSame(
            [TicketPrintType::Equipment->value, TicketPrintType::Client->value],
            $ticket->prints()->orderBy('id')->pluck('type')->map->value->all(),
        );
        $this->assertTrue($ticket->prints()->first()->printedBy->is($technician));
    }

    public function test_the_print_page_rejects_a_ticket_from_another_equipment(): void
    {
        $technician = User::factory()->technician()->create();
        $equipment = Equipment::factory()->create();
        $otherTicket = Ticket::factory()->create();

        $this->actingAs($technician)
            ->get(route('it-desk.equipment.print', [$equipment, $otherTicket]))
            ->assertNotFound();
    }

    public function test_regular_users_cannot_print_tickets(): void
    {
        $user = User::factory()->create();
        $equipment = Equipment::factory()->create();
        $ticket = Ticket::factory()->create(['equipment_id' => $equipment->id]);

        $this->actingAs($user)
            ->get(route('it-desk.equipment.print', [$equipment, $ticket]))
            ->assertForbidden();
    }

    public function test_code_128_generates_modules_and_rejects_invalid_characters(): void
    {
        $modules = Code128::modules('TCK-2026-0001');

        $this->assertNotEmpty($modules);
        $this->assertContainsOnly('bool', $modules);
        $this->assertStringContainsString('<svg', Code128::svg('TCK-2026-0001'));

        $this->expectException(InvalidArgumentException::class);

        Code128::modules('ñ');
    }
}
