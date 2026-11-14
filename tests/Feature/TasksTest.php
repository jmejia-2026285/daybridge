<?php

namespace Tests\Feature;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class TasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_open_the_tasks_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('tasks.index'))
            ->assertOk()
            ->assertSee('Tareas')
            ->assertSee('Nueva tarea');
    }

    public function test_users_can_create_a_task_for_another_person(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        $this->actingAs($creator);

        Volt::test('pages.tasks.index')
            ->set('title', 'Preparar la presentación')
            ->set('description', 'Incluir resultados del trimestre.')
            ->set('assignedTo', $assignee->id)
            ->set('priority', TaskPriority::High->value)
            ->set('dueDate', today()->addDays(3)->toDateString())
            ->call('save')
            ->assertHasNoErrors();

        $task = Task::query()->sole();

        $this->assertSame('Preparar la presentación', $task->title);
        $this->assertSame(TaskStatus::Pending, $task->status);
        $this->assertSame(TaskPriority::High, $task->priority);
        $this->assertTrue($task->creator->is($creator));
        $this->assertTrue($task->assignee->is($assignee));
        $this->assertNull($task->completed_at);
    }

    public function test_task_validation_matches_the_limits(): void
    {
        $creator = User::factory()->create();
        $inactiveUser = User::factory()->inactive()->create();

        $this->actingAs($creator);

        Volt::test('pages.tasks.index')
            ->set('title', str_repeat('a', 121))
            ->set('description', str_repeat('b', 2001))
            ->set('assignedTo', $inactiveUser->id)
            ->set('priority', 'URGENTE')
            ->set('status', 'INVALIDO')
            ->set('dueDate', 'no-es-fecha')
            ->call('save')
            ->assertHasErrors([
                'title',
                'description',
                'assignedTo',
                'priority',
                'status',
                'dueDate',
            ]);

        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_the_assignee_can_change_the_status_but_not_the_details(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $task = Task::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
        ]);

        $this->assertFalse($assignee->can('update', $task));
        $this->assertTrue($assignee->can('updateStatus', $task));

        $this->actingAs($assignee);

        Volt::test('pages.tasks.index')
            ->call('changeStatus', $task->id, TaskStatus::Completed->value)
            ->assertHasNoErrors();

        $task->refresh();

        $this->assertSame(TaskStatus::Completed, $task->status);
        $this->assertNotNull($task->completed_at);
    }

    public function test_only_the_creator_or_an_admin_can_update_or_delete_a_task(): void
    {
        $creator = User::factory()->create();
        $otherUser = User::factory()->create();
        $admin = User::factory()->admin()->create();
        $task = Task::factory()->create(['created_by' => $creator->id]);

        $this->assertTrue($creator->can('update', $task));
        $this->assertTrue($creator->can('delete', $task));
        $this->assertFalse($otherUser->can('update', $task));
        $this->assertFalse($otherUser->can('delete', $task));
        $this->assertTrue($admin->can('update', $task));
        $this->assertTrue($admin->can('delete', $task));
    }

    public function test_overdue_tasks_are_flagged_only_while_open(): void
    {
        $overdue = Task::factory()->overdue()->create();
        $completed = Task::factory()->completed()->create(['due_date' => today()->subDay()]);

        $this->assertTrue($overdue->isOverdue());
        $this->assertFalse($completed->isOverdue());
        $this->assertSame(1, Task::query()->overdue()->count());
    }

    public function test_filters_scope_tasks_by_owner_and_status(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        Task::factory()->create([
            'title' => 'Tarea propia pendiente',
            'assigned_to' => $user->id,
            'status' => TaskStatus::Pending,
        ]);

        Task::factory()->create([
            'title' => 'Tarea propia completada',
            'assigned_to' => $user->id,
            'status' => TaskStatus::Completed,
            'completed_at' => now(),
        ]);

        Task::factory()->create([
            'title' => 'Tarea ajena',
            'assigned_to' => $otherUser->id,
        ]);

        $this->actingAs($user);

        Volt::test('pages.tasks.index')
            ->set('onlyMine', true)
            ->assertSee('Tarea propia pendiente')
            ->assertSee('Tarea propia completada')
            ->assertDontSee('Tarea ajena');

        Volt::test('pages.tasks.index')
            ->set('onlyMine', true)
            ->set('statusFilter', TaskStatus::Completed->value)
            ->assertSee('Tarea propia completada')
            ->assertDontSee('Tarea propia pendiente');
    }

    public function test_users_can_toggle_a_task_between_completed_and_pending(): void
    {
        $user = User::factory()->create();
        $task = Task::factory()->create([
            'assigned_to' => $user->id,
            'created_by' => User::factory()->create()->id,
        ]);

        $this->actingAs($user);

        $component = Volt::test('pages.tasks.index')
            ->call('toggleCompleted', $task->id)
            ->assertHasNoErrors();

        $this->assertSame(TaskStatus::Completed, $task->fresh()->status);
        $this->assertNotNull($task->fresh()->completed_at);

        $component
            ->call('toggleCompleted', $task->id)
            ->assertHasNoErrors();

        $this->assertSame(TaskStatus::Pending, $task->fresh()->status);
        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_creators_can_delete_their_tasks(): void
    {
        $creator = User::factory()->create();
        $task = Task::factory()->create(['created_by' => $creator->id]);

        $this->actingAs($creator);

        Volt::test('pages.tasks.index')
            ->call('delete', $task->id)
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('tasks', ['id' => $task->id]);
    }

    public function test_creating_a_completed_task_sets_the_completion_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user);

        Volt::test('pages.tasks.index')
            ->set('title', 'Tarea ya cerrada')
            ->set('assignedTo', $user->id)
            ->set('status', TaskStatus::Completed->value)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertNotNull(Task::query()->sole()->completed_at);
    }

    public function test_creating_a_task_for_another_person_notifies_the_assignee(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();

        $this->actingAs($creator);

        $component = Volt::test('pages.tasks.index')
            ->set('title', 'Preparar la presentación')
            ->set('assignedTo', $assignee->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'type' => 'task_assigned',
            'notifiable_id' => $assignee->id,
            'notifiable_type' => User::class,
            'subject_type' => Task::class,
            'subject_id' => Task::query()->sole()->id,
            'actor_id' => $creator->id,
        ]);

        $component
            ->set('title', 'Preparar la presentación')
            ->set('assignedTo', $assignee->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('notifications', 2);

        $this->assertDatabaseCount('tasks', 2);
    }

    public function test_immediately_recreating_the_same_composition_does_not_spam_notifications(): void
    {
        $creator = User::factory()->create();

        $this->actingAs($creator);

        Volt::test('pages.tasks.index')
            ->set('title', 'Tarea para mí')
            ->set('assignedTo', $creator->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_reassigning_a_task_notifies_the_new_assignee(): void
    {
        $creator = User::factory()->create();
        $firstAssignee = User::factory()->create();
        $newAssignee = User::factory()->create();
        $task = Task::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $firstAssignee->id,
        ]);

        $this->actingAs($creator);

        Volt::test('pages.tasks.index')
            ->call('edit', $task->id)
            ->set('assignedTo', $newAssignee->id)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame($newAssignee->id, $task->fresh()->assigned_to);

        $this->assertDatabaseHas('notifications', [
            'type' => 'task_assigned',
            'notifiable_id' => $newAssignee->id,
            'notifiable_type' => User::class,
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'actor_id' => $creator->id,
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_completing_someone_elses_task_notifies_the_creator(): void
    {
        $creator = User::factory()->create();
        $assignee = User::factory()->create();
        $task = Task::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $assignee->id,
        ]);

        $this->actingAs($assignee);

        Volt::test('pages.tasks.index')
            ->call('changeStatus', $task->id, TaskStatus::Completed->value)
            ->assertHasNoErrors();

        $this->assertDatabaseHas('notifications', [
            'type' => 'task_completed',
            'notifiable_id' => $creator->id,
            'notifiable_type' => User::class,
            'subject_type' => Task::class,
            'subject_id' => $task->id,
            'actor_id' => $assignee->id,
        ]);

        Volt::test('pages.tasks.index')
            ->call('changeStatus', $task->id, TaskStatus::InProgress->value)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_completing_your_own_task_does_not_notify_you(): void
    {
        $creator = User::factory()->create();
        $task = Task::factory()->create([
            'created_by' => $creator->id,
            'assigned_to' => $creator->id,
        ]);

        $this->actingAs($creator);

        Volt::test('pages.tasks.index')
            ->call('changeStatus', $task->id, TaskStatus::Completed->value)
            ->assertHasNoErrors();

        $this->assertDatabaseCount('notifications', 0);
    }
}
