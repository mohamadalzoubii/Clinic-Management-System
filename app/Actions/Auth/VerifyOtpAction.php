<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\VerifyOtpData;
use App\Enums\OtpType;
use App\Enums\UserStatus;
use App\Models\OtpCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class VerifyOtpAction
{
    public function execute(VerifyOtpData $dto)
    {
        $otpRecord = OtpCode::forEmailAndType($dto->email, OtpType::VERIFICATION)
            ->valid()
            ->first();

        if (!$otpRecord) {
            throw ValidationException::withMessages([
                'code' => 'the code is not correct or expiers'
            ]);
        }

        if (!Hash::check($dto->code, $otpRecord->code)) {
            throw ValidationException::withmessages([
                'code' => 'the code uncorrect'
            ]);
        }

        $user = User::where('email', $dto->email)->first();
        $user->email_verified_at = now();
        $user->user_status = UserStatus::APPROVED;
        $user->save();

        $otpRecord->delete();

        return $user;
    }
}
