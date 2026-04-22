<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class PatientPolicy
{
    use HandlesAuthorization;

    public function update(User $user, Patient $targetPatient): bool
    {
        if ($user->patient?->id === $targetPatient->id) {
            return true;
        }

        return $user->hasPermissionTo(PermissionEnum::UPDATE->value);
    }
}
