<?php

namespace App\Policies;

use App\Models\Document;
use App\Models\User;

class DocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Document $document): bool
    {
        return $user->is_active;
    }

    public function create(User $user): bool
    {
        return $user->canManageContent();
    }

    public function update(User $user, Document $document): bool
    {
        return $user->isAdmin() || $user->is($document->uploader);
    }

    public function delete(User $user, Document $document): bool
    {
        return $user->isAdmin() || $user->is($document->uploader);
    }
}
