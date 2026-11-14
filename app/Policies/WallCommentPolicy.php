<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WallComment;

class WallCommentPolicy
{
    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function delete(User $user, WallComment $comment): bool
    {
        return $user->isAdmin() || $user->is($comment->author);
    }
}
