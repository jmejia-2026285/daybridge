<?php

namespace App\Policies;

use App\Models\InventoryItem;
use App\Models\User;

class InventoryItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function view(User $user, InventoryItem $item): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function create(User $user): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function update(User $user, InventoryItem $item): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function delete(User $user, InventoryItem $item): bool
    {
        return $user->isAdmin() || $user->isItDeskAdmin();
    }

    private function hasDeskAccess(User $user): bool
    {
        return $user->isAdmin() || $user->isItDeskMember();
    }
}
