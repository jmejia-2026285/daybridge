<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;
use Tests\TestCase;

class ContentAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('admin.content'))->assertRedirect(route('login'));
    }

    public function test_regular_users_cannot_enter_the_content_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('admin.content'))
            ->assertForbidden();
    }

    public function test_editors_and_admins_can_enter_the_content_admin(): void
    {
        $editor = User::factory()->editor()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($editor)->get(route('admin.content'))->assertOk();
        $this->actingAs($admin)->get(route('admin.content'))->assertOk();
    }

    public function test_the_feed_shows_the_manage_link_only_to_content_managers(): void
    {
        $user = User::factory()->create();
        $editor = User::factory()->editor()->create();

        $this->actingAs($user)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertDontSee(route('admin.content'));

        $this->actingAs($editor)
            ->get(route('feed.index'))
            ->assertOk()
            ->assertSee(route('admin.content'));
    }

    public function test_admin_can_create_a_published_comunicado(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.content')
            ->set('title', 'Protocolo de seguridad v3')
            ->set('type', Post::TYPE_NOTICE)
            ->set('areaId', (string) $area->id)
            ->set('body', 'Nuevas reglas de seguridad para toda la planta.')
            ->set('isFeatured', true)
            ->set('publishNow', true)
            ->call('save')
            ->assertHasNoErrors();

        $post = Post::query()->sole();

        $this->assertSame('Protocolo de seguridad v3', $post->title);
        $this->assertSame(Post::TYPE_NOTICE, $post->type);
        $this->assertSame($area->id, $post->area_id);
        $this->assertTrue($post->is_featured);
        $this->assertNotNull($post->published_at);
        $this->assertTrue($post->isPublished());
        $this->assertSame(Str::limit($post->body, 280), $post->excerpt);
        $this->assertTrue($post->author->is($admin));
    }

    public function test_admin_can_save_a_draft_comunicado(): void
    {
        $admin = User::factory()->admin()->create();
        $area = Area::factory()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.content')
            ->set('title', 'Borrador interno')
            ->set('areaId', (string) $area->id)
            ->set('body', 'Pendiente de revisión legal.')
            ->set('excerpt', 'Resumen manual del borrador')
            ->call('save')
            ->assertHasNoErrors();

        $post = Post::query()->sole();

        $this->assertNull($post->published_at);
        $this->assertSame('Resumen manual del borrador', $post->excerpt);
        $this->assertFalse($post->is_featured);

        $this->actingAs(User::factory()->create())
            ->get(route('feed.index'))
            ->assertOk()
            ->assertDontSee('Borrador interno');
    }

    public function test_comunicado_creation_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Volt::test('pages.admin.content')
            ->set('title', '')
            ->set('body', '')
            ->set('publishedAt', 'ayer')
            ->call('save')
            ->assertHasErrors(['title', 'body', 'publishedAt']);

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_admin_can_edit_and_pin_any_comunicado(): void
    {
        $admin = User::factory()->admin()->create();
        $author = User::factory()->editor()->create();
        $post = Post::factory()->create([
            'user_id' => $author->id,
            'title' => 'Título original',
            'is_featured' => false,
        ]);

        $this->actingAs($admin);

        Volt::test('pages.admin.content')
            ->call('openEdit', $post->id)
            ->assertSet('title', 'Título original')
            ->set('title', 'Título corregido')
            ->set('publishedAt', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors();

        $post->refresh();

        $this->assertSame('Título corregido', $post->title);
        $this->assertFalse($post->isPublished());
        $this->assertTrue(Carbon::parse($post->published_at)->isTomorrow());

        Volt::test('pages.admin.content')
            ->call('toggleFeatured', $post->id)
            ->assertHasNoErrors();

        $this->assertTrue($post->fresh()->is_featured);
    }

    public function test_editors_can_only_edit_their_own_or_area_comunicados(): void
    {
        $area = Area::factory()->create();
        $editor = User::factory()->editor()->create(['area_id' => $area->id]);
        $foreignAuthor = User::factory()->create();
        $ownPost = Post::factory()->create(['user_id' => $editor->id, 'title' => 'Propio']);
        $foreignPost = Post::factory()->create(['user_id' => $foreignAuthor->id, 'title' => 'De otra área']);

        $this->actingAs($editor);

        $this->assertTrue($editor->can('update', $ownPost));
        $this->assertFalse($editor->can('update', $foreignPost));
        $this->assertTrue($editor->can('delete', $ownPost));
        $this->assertFalse($editor->can('delete', $foreignPost));

        Volt::test('pages.admin.content')
            ->call('openEdit', $ownPost->id)
            ->set('title', 'Propio corregido')
            ->set('publishNow', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Propio corregido', $ownPost->fresh()->title);
        $this->assertSame('De otra área', $foreignPost->fresh()->title);
    }

    public function test_delete_comunicado_follows_the_policy(): void
    {
        $admin = User::factory()->admin()->create();
        $editor = User::factory()->editor()->create();
        $other = User::factory()->editor()->create();
        $post = Post::factory()->create(['user_id' => $editor->id, 'title' => 'Candidato a eliminación']);

        $this->assertTrue($admin->can('delete', $post));
        $this->assertTrue($editor->can('delete', $post));
        $this->assertFalse($other->can('delete', $post));

        $this->actingAs($editor);

        Volt::test('pages.admin.content')
            ->call('confirmDelete', $post->id)
            ->call('deletePost')
            ->assertHasNoErrors();

        $this->assertModelMissing($post);
    }

    public function test_counts_and_search_reflect_the_comunicados(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        Post::factory()->count(3)->create();
        Post::factory()->create(['published_at' => null, 'title' => 'Borrador interno administrativo']);
        Post::factory()->create(['title' => 'Protocolo único de búsqueda']);

        $this->get(route('admin.content', ['q' => 'Protocolo único']))
            ->assertOk()
            ->assertSee('Protocolo único de búsqueda')
            ->assertDontSee('Borrador interno administrativo');

        Volt::test('pages.admin.content')
            ->set('search', 'Borrador interno')
            ->assertSee('Borrador interno administrativo')
            ->assertDontSee('Protocolo único de búsqueda')
            ->call('clearSearch')
            ->assertSee('Protocolo único de búsqueda');
    }
}
