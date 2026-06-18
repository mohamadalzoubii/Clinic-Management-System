<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    public function before(User $user): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'doctor', 'secretary', 'patient']);
    }

    public function view(User $user, Doctor $doctor): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Doctor $doctor): bool
    {
        if ($user->hasRole('doctor')) {
            return $user->doctor?->id === $doctor->id;
        }

        return false;
    }



    public function delete(User $user, Doctor $doctor): bool
    {
        return false;
    }
}
