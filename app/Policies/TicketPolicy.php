<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function create(User $user): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function print(User $user, Ticket $ticket): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $user->isItDeskAdmin();
    }

    private function hasDeskAccess(User $user): bool
    {
        return $user->isAdmin() || $user->isItDeskMember();
    }
}
