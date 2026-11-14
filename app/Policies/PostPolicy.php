<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Post $post): bool
    {
        return $post->isPublished() || $user->isAdmin() || $user->is($post->author);
    }

    public function create(User $user): bool
    {
        return $user->canManageContent();
    }

    public function update(User $user, Post $post): bool
    {
        return $user->isAdmin()
            || ($user->isEditor() && $user->is($post->author))
            || ($user->isEditor() && $user->area_id === $post->area_id);
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->isAdmin() || $user->is($post->author);
    }

    public function publish(User $user, Post $post): bool
    {
        return $this->update($user, $post);
    }
}
