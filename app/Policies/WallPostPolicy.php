<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WallPost;

class WallPostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, WallPost $post): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, WallPost $post): bool
    {
        return $user->isAdmin() || $user->is($post->author);
    }

    public function delete(User $user, WallPost $post): bool
    {
        return $user->isAdmin() || $user->is($post->author);
    }

    public function pin(User $user, WallPost $post): bool
    {
        return $user->isAdmin();
    }
}
