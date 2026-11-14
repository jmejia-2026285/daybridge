<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Task;
use App\Models\User;
use App\Models\WallPost;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
    }

    public function test_wall_notifications_render_and_link_the_wall(): void
    {
        $recipient = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Autora del muro']);
        $post = WallPost::factory()->create();

        Notification::query()->create([
            'type' => 'wall_reaction',
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'subject_type' => WallPost::class,
            'subject_id' => $post->id,
            'actor_id' => $actor->id,
            'data' => ['message' => 'Reaccionó a tu publicación en el muro.'],
        ]);

        Notification::query()->create([
            'type' => 'wall_comment',
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'subject_type' => WallPost::class,
            'subject_id' => $post->id,
            'actor_id' => $actor->id,
            'data' => ['message' => 'Comentó en tu publicación del muro.'],
        ]);

        $this->actingAs($recipient)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Reaccionaron en el muro')
            ->assertSee('Comentaron en el muro')
            ->assertSee('Autora del muro')
            ->assertSee(route('wall.index'));
    }

    public function test_task_notifications_render_even_without_a_direct_link(): void
    {
        $recipient = User::factory()->create();
        $actor = User::factory()->create();
        $task = Task::factory()->create();

        Notification::query()->create([
            'type' => 'task_assigned',
            'notifiable_type' => User::class,
            'notifiable_id' => $recipient->id,
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'actor_id' => $actor->id,
            'data' => ['message' => 'Te asignaron la tarea «Subir archivo».'],
        ]);

        $this->actingAs($recipient)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Te asignaron una tarea')
            ->assertSee('Te asignaron la tarea «Subir archivo».');
    }

    public function test_notifications_only_show_mine_and_mark_them_read_when_requested(): void
    {
        $me = User::factory()->create();
        $other = User::factory()->create();
        $post = WallPost::factory()->create();

        Notification::query()->create([
            'type' => 'wall_reaction',
            'notifiable_type' => User::class,
            'notifiable_id' => $me->id,
            'subject_type' => WallPost::class,
            'subject_id' => $post->id,
            'actor_id' => $other->id,
            'data' => ['message' => 'Para mí.'],
        ]);

        Notification::query()->create([
            'type' => 'wall_reaction',
            'notifiable_type' => User::class,
            'notifiable_id' => $other->id,
            'subject_type' => WallPost::class,
            'subject_id' => $post->id,
            'actor_id' => $me->id,
            'data' => ['message' => 'Para otra persona.'],
        ]);

        $this->actingAs($me)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Para mí.')
            ->assertDontSee('Para otra persona.');
    }
}
