<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class SecretaryPolicy
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
        return $user->hasPermissionTo(PermissionEnum::CREATE->value);
    }

    public function update(User $user, $secretary): bool
    {
        return $user->hasPermissionTo(PermissionEnum::UPDATE->value);
    }

    public function delete(User $user, $secretary): bool
    {
        return $user->hasPermissionTo(PermissionEnum::DELETE->value);
    }
}
