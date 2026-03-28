<?php

namespace App\DTOs\Auth;

class LoginUserData
{
    /**
     * Create a new class instance.
     */
    public function __construct(public readonly string $email, public readonly string $password)
    {
        //
    }
}
