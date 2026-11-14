<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BirthdaysPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/cumpleanos')->assertRedirect('/login');
    }

    public function test_birthdays_page_renders_todays_celebrations_and_upcoming_birthdays(): void
    {
        $this->travelTo('2026-11-04 10:00:00');

        $user = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Personas']);

        User::factory()->create([
            'name' => 'Lucía Ferrer',
            'area_id' => $area->id,
            'birth_date' => '1995-11-04',
        ]);

        User::factory()->create([
            'name' => 'Diego Salas',
            'area_id' => $area->id,
            'birth_date' => '1990-11-10',
        ]);

        $this->actingAs($user)
            ->get('/cumpleanos')
            ->assertOk()
            ->assertSee('Lucía Ferrer')
            ->assertSee('Diego Salas')
            ->assertSee('Próximos 60 días')
            ->assertSee('Próxima semana');
    }

    public function test_birthdays_page_renders_an_empty_state_without_celebrations(): void
    {
        $this->travelTo('2026-11-04 10:00:00');

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/cumpleanos')
            ->assertOk()
            ->assertSee('Hoy no hay velas que soplar.');
    }

    public function test_inactive_people_do_not_appear_as_celebrating_or_upcoming(): void
    {
        $this->travelTo('2026-11-04 10:00:00');

        $user = User::factory()->create();
        $area = Area::factory()->create();

        $inactiveToday = User::factory()->inactive()->create([
            'name' => 'Bruno Inactivo Hoy',
            'area_id' => $area->id,
            'birth_date' => '1995-11-04',
        ]);

        $inactiveUpcoming = User::factory()->inactive()->create([
            'name' => 'Carla Inactiva Próxima',
            'area_id' => $area->id,
            'birth_date' => '1990-11-10',
        ]);

        $this->actingAs($user)
            ->get('/cumpleanos')
            ->assertOk()
            ->assertDontSee('Bruno Inactivo Hoy')
            ->assertDontSee('Carla Inactiva Próxima');

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertDontSee('Bruno Inactivo Hoy');
    }
}
