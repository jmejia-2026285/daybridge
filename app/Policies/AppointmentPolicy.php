<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function create(User $user): bool
    {
        return $this->hasDeskAccess($user);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin()
            || $user->isItDeskAdmin()
            || $user->is($appointment->technician);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $this->update($user, $appointment);
    }

    private function hasDeskAccess(User $user): bool
    {
        return $user->isAdmin() || $user->isItDeskMember();
    }
}
