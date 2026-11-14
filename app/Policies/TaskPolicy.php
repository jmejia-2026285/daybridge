<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Task $task): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->is($task->creator);
    }

    public function updateStatus(User $user, Task $task): bool
    {
        return $this->update($user, $task) || $user->is($task->assignee);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdmin() || $user->is($task->creator);
    }
}
