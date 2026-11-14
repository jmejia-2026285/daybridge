<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class FeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('feed.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_see_only_published_posts(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();

        Post::factory()->create([
            'user_id' => $author->id,
            'title' => 'Comunicado publicado',
            'published_at' => now()->subHour(),
        ]);

        Post::factory()->create([
            'user_id' => $author->id,
            'title' => 'Borrador sin publicar',
            'published_at' => null,
        ]);

        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee('Comunicado publicado')
            ->assertDontSee('Borrador sin publicar');
    }

    public function test_the_feed_filters_by_search_area_and_type(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Operaciones', 'slug' => 'operaciones']);
        $otherArea = Area::factory()->create(['name' => 'Finanzas', 'slug' => 'finanzas']);

        Post::factory()->create([
            'area_id' => $area->id,
            'title' => 'Nuevo protocolo de viajes',
            'type' => Post::TYPE_NOTICE,
        ]);

        Post::factory()->create([
            'area_id' => $otherArea->id,
            'title' => 'Resultados del trimestre',
            'type' => Post::TYPE_NEWS,
        ]);

        $this->actingAs($user);

        Volt::test('pages.feed.index')
            ->set('search', 'protocolo')
            ->assertSee('Nuevo protocolo de viajes')
            ->assertDontSee('Resultados del trimestre');

        Volt::test('pages.feed.index')
            ->set('area', 'operaciones')
            ->assertSee('Nuevo protocolo de viajes')
            ->assertDontSee('Resultados del trimestre');

        Volt::test('pages.feed.index')
            ->set('type', Post::TYPE_NEWS)
            ->assertSee('Resultados del trimestre')
            ->assertDontSee('Nuevo protocolo de viajes');
    }

    public function test_authors_can_open_their_unpublished_posts_but_others_cannot(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'title' => 'Borrador privado',
            'body' => 'Contenido todavía en revisión.',
            'published_at' => null,
        ]);

        $this->actingAs($author)
            ->get(route('feed.show', $post))
            ->assertOk()
            ->assertSee('Borrador privado')
            ->assertSee('Contenido todavía en revisión.');

        $this->actingAs($otherUser)
            ->get(route('feed.show', $post))
            ->assertForbidden();
    }

    public function test_the_detail_page_shows_the_body_and_related_posts_from_the_same_area(): void
    {
        $user = User::factory()->create();
        $area = Area::factory()->create(['name' => 'Personas']);
        $otherArea = Area::factory()->create(['name' => 'Tecnología']);

        $post = Post::factory()->create([
            'area_id' => $area->id,
            'title' => 'Comunicado principal',
            'body' => 'Primer párrafo del comunicado.'.PHP_EOL.PHP_EOL.'Segundo párrafo del comunicado.',
        ]);

        Post::factory()->create([
            'area_id' => $area->id,
            'title' => 'Comunicado relacionado',
        ]);

        Post::factory()->create([
            'area_id' => $otherArea->id,
            'title' => 'Comunicado de otra área',
        ]);

        $this->actingAs($user)
            ->get(route('feed.show', $post))
            ->assertOk()
            ->assertSee('Comunicado principal')
            ->assertSee('Primer párrafo del comunicado.')
            ->assertSee('Segundo párrafo del comunicado.')
            ->assertSee('Comunicado relacionado')
            ->assertDontSee('Comunicado de otra área');
    }

    public function test_the_home_page_links_to_the_full_feed(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('home'))
            ->assertOk()
            ->assertSee(route('feed.index'))
            ->assertSee('Ver todos');
    }
}
