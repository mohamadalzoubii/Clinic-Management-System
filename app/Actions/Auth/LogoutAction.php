<?php

namespace App\Actions\Auth;

use App\Models\User;

class LogoutAction
{
    /**
     * Create a new class instance.
     */
    public function execute(User $user)
    {
        $user->tokens()->delete();
    }
}
