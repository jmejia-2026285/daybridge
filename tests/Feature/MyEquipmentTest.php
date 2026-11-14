<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Models\Equipment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MyEquipmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('it-desk.my-equipment'))
            ->assertRedirect(route('login'));
    }

    public function test_inactive_users_are_signed_out(): void
    {
        $user = User::factory()->inactive()->create();

        $this->actingAs($user)
            ->get(route('it-desk.my-equipment'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_regular_users_can_open_my_equipment_from_the_module_menu(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('it-desk.my-equipment'))
            ->assertOk()
            ->assertSee('Mi equipo');

        $this->actingAs($user)
            ->get('/')
            ->assertSee(route('it-desk.my-equipment'));
    }

    public function test_users_only_see_equipment_registered_with_their_own_email(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@daybridge.test',
        ]);

        $ownEquipment = Equipment::factory()->create([
            'client_email' => 'ana@daybridge.test',
            'type' => 'Laptop',
            'brand' => 'Dell',
            'model' => 'Latitude 5440',
        ]);

        $otherEquipment = Equipment::factory()->create([
            'client_email' => 'otra.persona@daybridge.test',
        ]);

        $this->actingAs($user)
            ->get(route('it-desk.my-equipment'))
            ->assertOk()
            ->assertSee($ownEquipment->code)
            ->assertSee('Laptop Dell Latitude 5440')
            ->assertDontSee($otherEquipment->code);
    }

    public function test_users_see_the_status_of_their_equipment_and_its_tickets(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@daybridge.test',
        ]);

        $equipment = Equipment::factory()->inProgress()->create([
            'client_email' => 'ana@daybridge.test',
            'diagnosis' => 'Se reemplazó el disco.',
            'notes' => 'Nota interna que no debe mostrarse.',
        ]);

        $ticket = Ticket::factory()->inProgress()->create([
            'equipment_id' => $equipment->id,
            'description' => 'El equipo no arranca.',
        ]);

        $this->actingAs($user)
            ->get(route('it-desk.my-equipment'))
            ->assertOk()
            ->assertSee($equipment->code)
            ->assertSee('En proceso')
            ->assertSee($ticket->folio)
            ->assertSee('El equipo no arranca.')
            ->assertSee('Se reemplazó el disco.')
            ->assertDontSee('Nota interna que no debe mostrarse.');
    }

    public function test_users_without_registered_equipment_see_the_empty_state(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@daybridge.test',
        ]);

        Equipment::factory()->create([
            'client_email' => 'otra.persona@daybridge.test',
        ]);

        $this->actingAs($user)
            ->get(route('it-desk.my-equipment'))
            ->assertOk()
            ->assertSee('No hay equipos registrados con tu correo.')
            ->assertSee('ana@daybridge.test');
    }

    public function test_summary_counts_split_equipment_in_service_and_ready(): void
    {
        $user = User::factory()->create([
            'email' => 'ana@daybridge.test',
        ]);

        Equipment::factory()->create([
            'client_email' => 'ana@daybridge.test',
            'status' => ServiceStatus::Pending,
        ]);

        Equipment::factory()->create([
            'client_email' => 'ana@daybridge.test',
            'status' => ServiceStatus::InProgress,
        ]);

        Equipment::factory()->create([
            'client_email' => 'ana@daybridge.test',
            'status' => ServiceStatus::Completed,
        ]);

        $this->actingAs($user)
            ->get(route('it-desk.my-equipment'))
            ->assertOk()
            ->assertSeeInOrder(['Equipos', '3', 'En servicio', '2', 'Listos', '1']);
    }
}
