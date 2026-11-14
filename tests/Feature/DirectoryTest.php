<?php

namespace Tests\Feature;

use App\Enums\ItDeskRole;
use App\Enums\UserRole;
use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('directory.index'))->assertRedirect(route('login'));
    }

    public function test_the_directory_lists_only_active_people(): void
    {
        $viewer = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Personas']);

        User::factory()->create([
            'name' => 'Ana Activa',
            'area_id' => $area->id,
        ]);

        User::factory()->inactive()->create([
            'name' => 'Bruno Inactivo',
            'area_id' => $area->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('directory.index'))
            ->assertOk()
            ->assertSee('Directorio')
            ->assertSee('Ana Activa')
            ->assertDontSee('Bruno Inactivo');
    }

    public function test_people_can_be_searched_by_name_role_email_or_area(): void
    {
        $viewer = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Tecnología']);
        $otherArea = Area::factory()->create(['name' => 'Finanzas']);

        User::factory()->create([
            'name' => 'María Castillo',
            'email' => 'maria.castillo@daybridge.test',
            'job_title' => 'Ingeniera de plataforma',
            'area_id' => $area->id,
        ]);

        User::factory()->create([
            'name' => 'Luis Arévalo',
            'email' => 'luis.arevalo@daybridge.test',
            'job_title' => 'Analista financiero',
            'area_id' => $otherArea->id,
        ]);

        $this->actingAs($viewer);

        Volt::test('pages.directory.index')
            ->set('search', 'María')
            ->assertSee('María Castillo')
            ->assertDontSee('Luis Arévalo');

        Volt::test('pages.directory.index')
            ->set('search', 'Ingeniera')
            ->assertSee('María Castillo')
            ->assertDontSee('Luis Arévalo');

        Volt::test('pages.directory.index')
            ->set('search', 'luis.arevalo')
            ->assertSee('Luis Arévalo')
            ->assertDontSee('María Castillo');

        Volt::test('pages.directory.index')
            ->set('search', 'Finanzas')
            ->assertSee('Luis Arévalo')
            ->assertDontSee('María Castillo');
    }

    public function test_people_can_be_filtered_by_area(): void
    {
        $viewer = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Operaciones', 'slug' => 'operaciones']);
        $otherArea = Area::factory()->create(['name' => 'Crecimiento', 'slug' => 'crecimiento']);

        User::factory()->create(['name' => 'Diego Salazar', 'area_id' => $area->id]);
        User::factory()->create(['name' => 'Andrea Figueroa', 'area_id' => $otherArea->id]);

        $this->actingAs($viewer);

        Volt::test('pages.directory.index')
            ->set('area', 'operaciones')
            ->assertSee('Diego Salazar')
            ->assertDontSee('Andrea Figueroa');

        Volt::test('pages.directory.index')
            ->set('area', 'crecimiento')
            ->assertSee('Andrea Figueroa')
            ->assertDontSee('Diego Salazar');
    }

    public function test_the_profile_page_shows_contact_and_context(): void
    {
        $viewer = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Tecnología']);

        $person = User::factory()->technician()->create([
            'name' => 'María Castillo',
            'email' => 'maria.castillo@daybridge.test',
            'job_title' => 'Ingeniera de plataforma',
            'area_id' => $area->id,
            'birth_date' => '1990-03-12',
            'joined_at' => '2021-06-01',
        ]);

        $this->actingAs($viewer)
            ->get(route('directory.show', $person))
            ->assertOk()
            ->assertSee('María Castillo')
            ->assertSee('Ingeniera de plataforma')
            ->assertSee('Tecnología')
            ->assertSee('maria.castillo@daybridge.test')
            ->assertSee('12 de marzo')
            ->assertSee('2021')
            ->assertSee(ItDeskRole::Tecnico->label());
    }

    public function test_inactive_people_do_not_have_a_profile(): void
    {
        $viewer = User::factory()->create();
        $person = User::factory()->inactive()->create();

        $this->actingAs($viewer)
            ->get(route('directory.show', $person))
            ->assertNotFound();
    }

    public function test_the_profile_lists_active_colleagues_from_the_same_area(): void
    {
        $viewer = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Personas']);
        $otherArea = Area::factory()->create(['name' => 'Finanzas']);

        $person = User::factory()->create([
            'name' => 'Sofía Marroquín',
            'area_id' => $area->id,
        ]);

        User::factory()->create([
            'name' => 'Karla Solís',
            'area_id' => $area->id,
        ]);

        User::factory()->inactive()->create([
            'name' => 'Colega Inactivo',
            'area_id' => $area->id,
        ]);

        User::factory()->create([
            'name' => 'Ricardo Quiñónez',
            'area_id' => $otherArea->id,
        ]);

        $this->actingAs($viewer)
            ->get(route('directory.show', $person))
            ->assertOk()
            ->assertSee('Karla Solís')
            ->assertDontSee('Colega Inactivo')
            ->assertDontSee('Ricardo Quiñónez');
    }

    public function test_the_profile_lists_only_published_posts_by_the_person(): void
    {
        $viewer = User::factory()->create();
        $person = User::factory()->create();

        Post::factory()->create([
            'user_id' => $person->id,
            'title' => 'Comunicado publicado',
            'published_at' => now()->subHour(),
        ]);

        Post::factory()->create([
            'user_id' => $person->id,
            'title' => 'Borrador privado',
            'published_at' => null,
        ]);

        $this->actingAs($viewer)
            ->get(route('directory.show', $person))
            ->assertOk()
            ->assertSee('Comunicado publicado')
            ->assertDontSee('Borrador privado');
    }

    public function test_the_directory_links_to_each_profile(): void
    {
        $viewer = User::factory()->create();
        $person = User::factory()->create(['name' => 'Gabriela Monzón']);

        $this->actingAs($viewer)
            ->get(route('directory.index'))
            ->assertOk()
            ->assertSee(route('directory.show', $person));
    }

    public function test_the_profile_shows_phone_with_call_and_email_actions(): void
    {
        $viewer = User::factory()->create();
        $person = User::factory()->create([
            'name' => 'Mario Chávez',
            'email' => 'mario.chavez@daybridge.test',
            'phone' => '+502 5550 1122',
        ]);

        $response = $this->actingAs($viewer)->get(route('directory.show', $person));

        $response->assertSee('tel:+50255501122');
        $response->assertSee('mailto:mario.chavez@daybridge.test');
        $response->assertSee('https://mail.google.com/mail/?view=cm&to=mario.chavez%40daybridge.test');
        $response->assertSee('Copiar correo');
    }

    public function test_admins_can_edit_a_person_data(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $area = Area::factory()->create(['name' => 'Operaciones']);
        $otherArea = Area::factory()->create(['name' => 'Finanzas']);
        $person = User::factory()->create([
            'name' => 'Nombre Viejo',
            'email' => 'viejo@daybridge.test',
            'email_verified_at' => now(),
            'area_id' => $area->id,
        ]);

        $this->actingAs($admin);

        Volt::test('pages.directory.show', ['person' => $person])
            ->call('edit')
            ->set('name', 'Nombre Nuevo')
            ->set('email', 'nuevo@daybridge.test')
            ->set('phone', '502 5512 2211')
            ->set('jobTitle', 'Analista nuevo')
            ->set('areaId', (string) $otherArea->id)
            ->call('save');

        $person->refresh();

        $this->assertSame('Nombre Nuevo', $person->name);
        $this->assertSame('nuevo@daybridge.test', $person->email);
        $this->assertSame('502 5512 2211', $person->phone);
        $this->assertSame('Analista nuevo', $person->job_title);
        $this->assertSame($otherArea->id, $person->area_id);
        $this->assertNull($person->email_verified_at);
    }

    public function test_non_admins_cannot_edit_a_person_data(): void
    {
        $viewer = User::factory()->create();
        $person = User::factory()->create(['name' => 'Nombre Original']);

        $this->assertFalse($viewer->can('update', $person));

        $this->actingAs($viewer);

        $component = Volt::test('pages.directory.show', ['person' => $person])
            ->set('name', 'Nombre Cambiado');

        try {
            $component->call('save');
        } catch (AuthorizationException) {
        }

        $person->refresh();

        $this->assertSame('Nombre Original', $person->name);
    }

    public function test_admin_editing_validates_email_and_phone(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin->value]);
        $person = User::factory()->create();

        $this->actingAs($admin);

        Volt::test('pages.directory.show', ['person' => $person])
            ->call('edit')
            ->set('phone', 'no telephone!')
            ->call('save')
            ->assertHasErrors(['phone']);

        Volt::test('pages.directory.show', ['person' => $person])
            ->call('edit')
            ->set('email', User::factory()->create()->email)
            ->call('save')
            ->assertHasErrors(['email' => 'unique']);
    }
}
