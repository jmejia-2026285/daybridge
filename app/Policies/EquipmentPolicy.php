<?php

namespace App\Policies;

use App\Models\Equipment;
use App\Models\User;

class EquipmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function view(User $user, Equipment $equipment): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function create(User $user): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function update(User $user, Equipment $equipment): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function delete(User $user, Equipment $equipment): bool
    {
        return $user->isAdmin() || $user->isItDeskAdmin();
    }

    private function hasDeskAccess(User $user): bool
    {
        return $user->isAdmin() || $user->isItDeskMember();
    }
}
