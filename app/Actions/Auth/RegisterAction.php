<?php

namespace App\Actions\Auth;

use App\DTOs\Auth\RegisterUserData;
use App\Enums\OtpType;
use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Facades\DB;

class RegisterAction
{
    public function __construct(private readonly OtpService $otpService) {}

    public function execute(RegisterUserData $dto)
    {
        return DB::transaction(function () use ($dto) {

            $user = User::create([
                'first_name' => $dto->first_name,
                'last_name' => $dto->last_name,
                'email' => $dto->email,
                'password' => $dto->password,
                'user_status' => UserStatus::PENDING,
            ]);

            $this->otpService->generateAndSend($user->email, OtpType::VERIFICATION);

            $user->assignRole(RoleEnum::PATIENT->value);
            $user->patient()->create();

            return $user;
        });
    }
}
