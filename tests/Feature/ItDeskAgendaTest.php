<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ItDeskAgendaTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_users_cannot_open_the_agenda(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('it-desk.agenda'))
            ->assertForbidden();
    }

    public function test_technicians_only_see_their_own_appointments(): void
    {
        $technician = User::factory()->technician()->create();
        $otherTechnician = User::factory()->technician()->create();

        Appointment::factory()->create([
            'technician_id' => $technician->id,
            'title' => 'Cita propia',
        ]);

        Appointment::factory()->create([
            'technician_id' => $otherTechnician->id,
            'title' => 'Cita ajena',
        ]);

        $this->actingAs($technician)
            ->get(route('it-desk.agenda'))
            ->assertOk()
            ->assertSee('Cita propia')
            ->assertDontSee('Cita ajena');
    }

    public function test_managers_can_see_and_filter_every_technicians_agenda(): void
    {
        $manager = User::factory()->itDeskAdmin()->create();
        $firstTechnician = User::factory()->technician()->create();
        $secondTechnician = User::factory()->technician()->create();

        Appointment::factory()->create([
            'technician_id' => $firstTechnician->id,
            'title' => 'Cita primera',
        ]);

        Appointment::factory()->create([
            'technician_id' => $secondTechnician->id,
            'title' => 'Cita segunda',
        ]);

        $this->actingAs($manager)
            ->get(route('it-desk.agenda'))
            ->assertOk()
            ->assertSee('Cita primera')
            ->assertSee('Cita segunda');

        Volt::test('pages.it-desk.agenda')
            ->set('technicianFilter', (string) $secondTechnician->id)
            ->assertSee('Cita segunda')
            ->assertDontSee('Cita primera');
    }

    public function test_the_week_view_includes_appointments_later_in_the_week(): void
    {
        $technician = User::factory()->technician()->create();
        $monday = today()->startOfWeek();
        $wednesday = $monday->copy()->addDays(2);

        Appointment::factory()->create([
            'technician_id' => $technician->id,
            'title' => 'Cita de mitad de semana',
            'scheduled_date' => $wednesday,
        ]);

        $this->actingAs($technician);

        Volt::test('pages.it-desk.agenda')
            ->set('anchorDate', $monday->toDateString())
            ->set('view', 'week')
            ->assertSee('Cita de mitad de semana');

        Volt::test('pages.it-desk.agenda')
            ->set('anchorDate', $monday->toDateString())
            ->set('view', 'day')
            ->assertDontSee('Cita de mitad de semana');
    }

    public function test_technicians_can_create_an_appointment_for_themselves(): void
    {
        $technician = User::factory()->technician()->create();

        $this->actingAs($technician);

        Volt::test('pages.it-desk.agenda')
            ->set('title', 'Revisión de laptop')
            ->set('description', 'Cambio de batería')
            ->set('scheduledDate', today()->toDateString())
            ->set('scheduledTime', '14:30')
            ->set('technicianId', $technician->id)
            ->call('save')
            ->assertHasNoErrors();

        $appointment = Appointment::query()->sole();

        $this->assertSame('Revisión de laptop', $appointment->title);
        $this->assertSame('Cambio de batería', $appointment->description);
        $this->assertSame('14:30', $appointment->timeLabel());
        $this->assertTrue($appointment->technician->is($technician));
        $this->assertFalse($appointment->completed);
    }

    public function test_technicians_cannot_assign_an_appointment_to_another_technician(): void
    {
        $technician = User::factory()->technician()->create();
        $otherTechnician = User::factory()->technician()->create();

        $this->actingAs($technician);

        Volt::test('pages.it-desk.agenda')
            ->set('title', 'Cita forzada')
            ->set('scheduledDate', today()->toDateString())
            ->set('scheduledTime', '09:00')
            ->set('technicianId', $otherTechnician->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue(Appointment::query()->sole()->technician->is($technician));
    }

    public function test_appointment_validation_matches_the_reference_limits(): void
    {
        $technician = User::factory()->technician()->create();

        $this->actingAs($technician);

        Volt::test('pages.it-desk.agenda')
            ->set('title', str_repeat('a', 101))
            ->set('description', str_repeat('b', 501))
            ->set('scheduledDate', '')
            ->set('scheduledTime', '25:99')
            ->set('technicianId', $technician->id)
            ->call('save')
            ->assertHasErrors([
                'title',
                'description',
                'scheduledDate',
                'scheduledTime',
            ]);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_appointments_can_be_edited_completed_and_reopened(): void
    {
        $technician = User::factory()->technician()->create();
        $appointment = Appointment::factory()->create([
            'technician_id' => $technician->id,
            'title' => 'Título original',
        ]);

        $this->actingAs($technician);

        $component = Volt::test('pages.it-desk.agenda')
            ->call('selectAppointment', $appointment->id)
            ->assertSet('title', 'Título original')
            ->set('title', 'Título actualizado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Título actualizado', $appointment->fresh()->title);

        $component
            ->call('toggleCompleted', $appointment->id)
            ->assertHasNoErrors();

        $this->assertTrue($appointment->fresh()->completed);

        $component
            ->call('toggleCompleted', $appointment->id)
            ->assertHasNoErrors();

        $this->assertFalse($appointment->fresh()->completed);
    }

    public function test_only_the_owner_or_a_manager_can_manage_an_appointment(): void
    {
        $technician = User::factory()->technician()->create();
        $otherTechnician = User::factory()->technician()->create();
        $manager = User::factory()->itDeskAdmin()->create();
        $appointment = Appointment::factory()->create(['technician_id' => $technician->id]);

        $this->assertTrue($technician->can('update', $appointment));
        $this->assertFalse($otherTechnician->can('update', $appointment));
        $this->assertTrue($manager->can('update', $appointment));
        $this->assertTrue($manager->can('delete', $appointment));
        $this->assertFalse($otherTechnician->can('delete', $appointment));
    }

    public function test_technicians_can_delete_their_own_appointments(): void
    {
        $technician = User::factory()->technician()->create();
        $appointment = Appointment::factory()->create(['technician_id' => $technician->id]);

        $this->actingAs($technician);

        Volt::test('pages.it-desk.agenda')
            ->call('delete', $appointment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('appointments', ['id' => $appointment->id]);
    }

    public function test_the_dashboard_links_to_the_agenda_and_scopes_today_for_technicians(): void
    {
        $technician = User::factory()->technician()->create();
        $otherTechnician = User::factory()->technician()->create();

        Appointment::factory()->create([
            'technician_id' => $technician->id,
            'title' => 'Cita de hoy propia',
        ]);

        Appointment::factory()->create([
            'technician_id' => $otherTechnician->id,
            'title' => 'Cita de hoy ajena',
        ]);

        $this->actingAs($technician)
            ->get(route('it-desk.dashboard'))
            ->assertOk()
            ->assertSee(route('it-desk.agenda'))
            ->assertSee('Cita de hoy propia')
            ->assertDontSee('Cita de hoy ajena');
    }
}
