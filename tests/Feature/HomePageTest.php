<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_home_renders_the_featured_post_and_the_feed(): void
    {
        $user = User::factory()->create();

        Post::factory()->featured()->create(['title' => 'Novedad principal']);
        Post::factory()->create(['title' => 'Nota secundaria']);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Novedad principal')
            ->assertSee('Nota secundaria');
    }

    public function test_home_filters_posts_by_area(): void
    {
        $user = User::factory()->create();
        $operations = Area::factory()->create(['name' => 'Operaciones', 'slug' => 'operaciones']);
        $technology = Area::factory()->create(['name' => 'Tecnología', 'slug' => 'tecnologia']);

        Post::factory()->create(['area_id' => $operations->id, 'title' => 'Solo operaciones']);
        Post::factory()->create(['area_id' => $technology->id, 'title' => 'Solo tecnología']);

        $this->actingAs($user)
            ->get('/?area=operaciones')
            ->assertOk()
            ->assertSee('Solo operaciones')
            ->assertDontSee('Solo tecnología');
    }

    public function test_home_renders_todays_birthdays(): void
    {
        $this->travelTo('2026-11-04 10:00:00');

        $user = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Personas']);

        User::factory()->create([
            'name' => 'Lucía Ferrer',
            'area_id' => $area->id,
            'birth_date' => '1995-11-04',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Cumpleaños')
            ->assertSee('Lucía Ferrer')
            ->assertSee('Felicitar');
    }

    public function test_home_renders_urgent_notices_and_recognition_formats(): void
    {
        $user = User::factory()->create();

        Post::factory()->urgent()->create(['title' => 'Aviso crítico']);
        Post::factory()->recognition()->create(['title' => 'Gracias, equipo']);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Urgente')
            ->assertSee('Aviso crítico')
            ->assertSee('Reconocimiento')
            ->assertSee('Gracias, equipo');
    }

    public function test_home_renders_an_empty_state_without_posts(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSee('Todavía no hay comunicados en esta área.');
    }
}
