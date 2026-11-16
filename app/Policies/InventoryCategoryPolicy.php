<?php

namespace App\Policies;

use App\Models\InventoryCategory;
use App\Models\User;

class InventoryCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin() || $user->isItDeskMember();
    }

    public function view(User $user, InventoryCategory $category): bool
    {
        return $user->isAdmin() || $user->isItDeskMember();
    }

    /**
     * Las categorías solo las administra el responsable del área:
     * así el listado no crece sin control.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isItDeskAdmin();
    }

    public function update(User $user, InventoryCategory $category): bool
    {
        return $user->isAdmin() || $user->isItDeskAdmin();
    }

    public function delete(User $user, InventoryCategory $category): bool
    {
        return $user->isAdmin() || $user->isItDeskAdmin();
    }
}
