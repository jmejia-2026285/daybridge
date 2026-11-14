<?php

namespace Tests\Feature;

use App\Enums\WallReactionType;
use App\Models\User;
use App\Models\WallComment;
use App\Models\WallPost;
use App\Models\WallReaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class WallTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('wall.index'))->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_open_the_wall(): void
    {
        $user = User::factory()->create();
        $post = WallPost::factory()->create([
            'body' => 'Mensaje visible para el equipo.',
        ]);

        $this->actingAs($user)
            ->get(route('wall.index'))
            ->assertOk()
            ->assertSee('Muro')
            ->assertSee('Mensaje visible para el equipo.')
            ->assertSee($post->author->name);
    }

    public function test_users_can_publish_a_message(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('pages.wall.index')
            ->set('body', 'Hoy celebramos el cierre del proyecto.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('body', '');

        $post = WallPost::query()->sole();

        $this->assertSame('Hoy celebramos el cierre del proyecto.', $post->body);
        $this->assertTrue($post->author->is($user));
        $this->assertFalse($post->is_pinned);
    }

    public function test_the_message_validation_matches_the_limit(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('pages.wall.index')
            ->set('body', '')
            ->call('save')
            ->assertHasErrors(['body' => 'required']);

        Volt::test('pages.wall.index')
            ->set('body', str_repeat('a', 1001))
            ->call('save')
            ->assertHasErrors(['body' => 'max']);

        $this->assertDatabaseCount('wall_posts', 0);
    }

    public function test_authors_can_edit_and_delete_their_own_messages(): void
    {
        $author = User::factory()->create();
        $post = WallPost::factory()->create([
            'user_id' => $author->id,
            'body' => 'Texto original',
        ]);

        $this->actingAs($author);

        Volt::test('pages.wall.index')
            ->call('edit', $post->id)
            ->assertSet('editingId', $post->id)
            ->set('body', 'Texto actualizado')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Texto actualizado', $post->fresh()->body);

        Volt::test('pages.wall.index')
            ->call('delete', $post->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('wall_posts', ['id' => $post->id]);
    }

    public function test_only_the_author_or_an_admin_can_manage_a_message(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $post = WallPost::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($author->can('update', $post));
        $this->assertTrue($author->can('delete', $post));
        $this->assertFalse($otherUser->can('update', $post));
        $this->assertFalse($otherUser->can('delete', $post));
        $this->assertTrue($admin->can('update', $post));
        $this->assertTrue($admin->can('delete', $post));
    }

    public function test_only_admins_can_pin_messages(): void
    {
        $user = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $post = WallPost::factory()->create();

        $this->assertFalse($user->can('pin', $post));
        $this->assertTrue($admin->can('pin', $post));

        $this->actingAs($admin);

        Volt::test('pages.wall.index')
            ->call('togglePin', $post->id)
            ->assertHasNoErrors();

        $this->assertTrue($post->fresh()->is_pinned);

        Volt::test('pages.wall.index')
            ->call('togglePin', $post->id)
            ->assertHasNoErrors();

        $this->assertFalse($post->fresh()->is_pinned);
    }

    public function test_pinned_messages_appear_first(): void
    {
        $user = User::factory()->create();

        WallPost::factory()->create(['body' => 'Mensaje reciente sin fijar']);
        WallPost::factory()->pinned()->create(['body' => 'Mensaje fijado']);

        $this->actingAs($user)
            ->get(route('wall.index'))
            ->assertOk()
            ->assertSeeInOrder(['Mensaje fijado', 'Mensaje reciente sin fijar']);
    }

    public function test_users_can_react_switch_and_remove_their_reaction(): void
    {
        $user = User::factory()->create();
        $post = WallPost::factory()->create();

        $this->actingAs($user);

        Volt::test('pages.wall.index')
            ->call('react', $post->id, WallReactionType::Like->value)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('wall_reactions', [
            'wall_post_id' => $post->id,
            'user_id' => $user->id,
            'type' => WallReactionType::Like->value,
        ]);

        Volt::test('pages.wall.index')
            ->call('react', $post->id, WallReactionType::Clap->value)
            ->assertHasNoErrors();

        $this->assertSame(1, WallReaction::query()->count());
        $this->assertSame(WallReactionType::Clap, $post->fresh()->reactionBy($user)->type);

        Volt::test('pages.wall.index')
            ->call('react', $post->id, WallReactionType::Clap->value)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('wall_reactions', 0);
    }

    public function test_invalid_reaction_types_are_rejected(): void
    {
        $user = User::factory()->create();
        $post = WallPost::factory()->create();

        $this->actingAs($user);

        Volt::test('pages.wall.index')
            ->call('react', $post->id, 'INVALIDO')
            ->assertHasErrors(['type']);

        $this->assertDatabaseCount('wall_reactions', 0);
    }

    public function test_users_can_comment_on_a_message(): void
    {
        $user = User::factory()->create();
        $post = WallPost::factory()->create();

        $this->actingAs($user);

        Volt::test('pages.wall.index')
            ->set('commentDrafts', [$post->id => 'Gracias por compartirlo.'])
            ->call('addComment', $post->id)
            ->assertHasNoErrors();

        $comment = WallComment::query()->sole();

        $this->assertSame('Gracias por compartirlo.', $comment->body);
        $this->assertTrue($comment->author->is($user));
        $this->assertTrue($comment->post->is($post));
    }

    public function test_comment_validation_matches_the_limit(): void
    {
        $user = User::factory()->create();
        $post = WallPost::factory()->create();

        $this->actingAs($user);

        Volt::test('pages.wall.index')
            ->set('commentDrafts', [$post->id => ''])
            ->call('addComment', $post->id)
            ->assertHasErrors(['commentDrafts.'.$post->id => 'required']);

        Volt::test('pages.wall.index')
            ->set('commentDrafts', [$post->id => str_repeat('a', 501)])
            ->call('addComment', $post->id)
            ->assertHasErrors(['commentDrafts.'.$post->id => 'max']);

        $this->assertDatabaseCount('wall_comments', 0);
    }

    public function test_only_the_comment_author_or_an_admin_can_delete_a_comment(): void
    {
        $author = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $comment = WallComment::factory()->create(['user_id' => $author->id]);

        $this->assertTrue($author->can('delete', $comment));
        $this->assertFalse($otherUser->can('delete', $comment));
        $this->assertTrue($admin->can('delete', $comment));

        $this->actingAs($author);

        Volt::test('pages.wall.index')
            ->call('deleteComment', $comment->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('wall_comments', ['id' => $comment->id]);
    }

    public function test_reactions_notify_the_author_without_duplicating_on_switch(): void
    {
        $user = User::factory()->create();
        $post = WallPost::factory()->create();

        $this->actingAs($user);

        Volt::test('pages.wall.index')
            ->call('react', $post->id, WallReactionType::Like->value)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'type' => 'wall_reaction',
            'notifiable_id' => $post->user_id,
            'notifiable_type' => User::class,
            'subject_type' => WallPost::class,
            'subject_id' => $post->id,
            'actor_id' => $user->id,
        ]);

        Volt::test('pages.wall.index')
            ->call('react', $post->id, WallReactionType::Clap->value)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_reacting_to_your_own_post_does_not_notify_you(): void
    {
        $author = User::factory()->create();
        $post = WallPost::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author);

        Volt::test('pages.wall.index')
            ->call('react', $post->id, WallReactionType::Like->value)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_comments_notify_the_post_author(): void
    {
        $commenter = User::factory()->create();
        $post = WallPost::factory()->create();

        $this->actingAs($commenter);

        Volt::test('pages.wall.index')
            ->set('commentDrafts', [$post->id => 'Gracias por compartirlo.'])
            ->call('addComment', $post->id)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'type' => 'wall_comment',
            'notifiable_id' => $post->user_id,
            'notifiable_type' => User::class,
            'subject_type' => WallPost::class,
            'subject_id' => $post->id,
            'actor_id' => $commenter->id,
        ]);
    }

    public function test_commenting_your_own_post_does_not_notify_you(): void
    {
        $author = User::factory()->create();
        $post = WallPost::factory()->create(['user_id' => $author->id]);

        $this->actingAs($author);

        Volt::test('pages.wall.index')
            ->set('commentDrafts', [$post->id => 'Comentario propio.'])
            ->call('addComment', $post->id)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('notifications', 0);
    }
}
