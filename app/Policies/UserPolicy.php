<?php

namespace App\Policies;

use App\Enums\PermissionEnum;
use App\Models\User;

class UserPolicy
{
   public function update(User $currentUser, User $targetUser): bool
    {
        
        if ($currentUser->id === $targetUser->id) {
            return true; 
        }

        return $currentUser->hasPermissionTo(PermissionEnum::UPDATE->value);
    }

    // فكره غير مكتبملو
}
