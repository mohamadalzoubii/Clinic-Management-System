<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginUserData;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    public function execute(LoginUserData $dto)
    {
        $user = User::where('email', $dto->email)->first();

        if (!$user || !Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'your email or your password is wrong plese try again'
            ]);
        }

        if (!$user->email_verified_at) {
            throw ValidationException::withMessages([
                'your account is not active plese enter the otp code'
            ]);
        }

        if ($user->user_status == UserStatus::SUSPENDED) {
            throw ValidationException::withMessages(['
                your account is suspended for help contact the admin
            ']);
        }

        $token = $user->createToken('API token for' . $user->email)->plainTextToken;
        return
            [
                'user' => $user,
                'token' => $token
            ];
    }
}
