<?php

namespace App\Policies;

use App\Models\Survey;
use App\Models\User;

class SurveyPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Survey $survey): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canManageContent();
    }

    public function update(User $user, Survey $survey): bool
    {
        return $user->isAdmin() || $user->is($survey->creator);
    }

    public function delete(User $user, Survey $survey): bool
    {
        return $user->isAdmin() || $user->is($survey->creator);
    }

    public function vote(User $user, Survey $survey): bool
    {
        return $user->is_active && $survey->isOpen();
    }
}
