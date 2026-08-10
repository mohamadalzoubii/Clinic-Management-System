<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientPolicy
{
    use HandlesAuthorization;

    public function before(User $user, string $ability): ?bool
    {
        if ($user->hasRole(RoleEnum::ADMIN->value)) {
            return true;
        }

        return null;
    }

    public function create(User $user): bool
    {
        if ($user->hasRole(RoleEnum::SECRETARY->value)) {
            return true;
        }

        return $user->hasPermissionTo(PermissionEnum::CREATE->value);
    }

    public function update(User $user, Patient $targetPatient): bool
    {
        if ($user->patient?->id === $targetPatient->id) {
            return true;
        }

        return $user->hasPermissionTo(PermissionEnum::UPDATE->value);
    }

    public function delete(User $user, Patient $targetPatient): bool
    {
        return $user->hasPermissionTo(PermissionEnum::DELETE->value);
    }
}
