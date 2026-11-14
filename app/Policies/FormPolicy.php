<?php

namespace App\Policies;

use App\Models\Form;
use App\Models\User;

class FormPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Form $form): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canManageContent();
    }

    public function update(User $user, Form $form): bool
    {
        return $user->isAdmin() || $user->is($form->creator);
    }

    public function delete(User $user, Form $form): bool
    {
        return $user->isAdmin() || $user->is($form->creator);
    }

    public function submit(User $user, Form $form): bool
    {
        return $user->is_active
            && $form->isOpen()
            && ! $form->hasSubmittedBy($user);
    }

    public function viewResponses(User $user, Form $form): bool
    {
        return $user->isAdmin() || $user->is($form->creator);
    }
}
