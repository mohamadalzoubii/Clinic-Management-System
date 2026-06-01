<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\LoginUserData;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginAction
{
    public function execute(LoginUserData $dto, string $expectedRole = 'patient', bool $requireOtp = true)
    {
        $user = User::where('email', $dto->email)->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw ValidationException::withMessages([
                'your email or your password is wrong plese try again',
            ]);
        }

        if ($expectedRole === 'patient' && $requireOtp && ! $user->email_verified_at) {
            throw ValidationException::withMessages([
                'your account is not active plese enter the otp code',
            ]);
        }

        if ($user->user_status == UserStatus::SUSPENDED) {
            throw ValidationException::withMessages([
                '
                your account is suspended for help contact the admin
            ',
            ]);
        }

        if (! $user->hasRole($expectedRole)) {
            throw ValidationException::withMessages(['you are not allowed to access this page']);
        }

        if ($expectedRole === 'doctor') {
            $user->loadMissing('doctor');
        } elseif ($expectedRole === 'admin') {
            $user->loadMissing('admin');
        } elseif ($expectedRole === 'secretary') {
            $user->loadMissing('secretary');
        } else {
            $user->loadMissing('patient');
        }

        $token = $user->createToken('API token for'.$user->email)->plainTextToken;

        return
            [
                'user' => $user,
                'token' => $token,
            ];
    }
}
