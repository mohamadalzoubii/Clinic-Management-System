<?php

namespace App\Services\Admin;

use App\DTOs\Secretary\StoreSecretaryData;
use App\DTOs\Secretary\UpdateSecretaryData;
use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Secretary;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SecretaryService
{
    public function store(StoreSecretaryData $dto): Secretary
    {
        return DB::transaction(function () use ($dto) {
            $user = User::create([
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'email' => $dto->email,
                'phone' => $dto->phone,
                'password' => Hash::make($dto->password),
                'user_status' => UserStatus::APPROVED->value,
            ]);

            $user->assignRole(RoleEnum::SECRETARY->value);

            return $user->secretary()->create([
                'work_days' => $dto->workDays,
                'monthly_salary' => $dto->monthlySalary,
            ]);
        });
    }

    public function update(Secretary $secretary, UpdateSecretaryData $dto): Secretary
    {
        return DB::transaction(function () use ($secretary, $dto) {
            if ($dto->hasUserChanges()) {
                $userPayload = array_filter([
                    'first_name' => $dto->firstName,
                    'last_name' => $dto->lastName,
                    'email' => $dto->email,
                    'phone' => $dto->phone,
                    'password' => $dto->password !== null ? Hash::make($dto->password) : null,
                ], static fn ($value) => $value !== null);

                $secretary->user->update($userPayload);
            }

            if ($dto->hasSecretaryChanges()) {
                $secretaryPayload = array_filter([
                    'work_days' => $dto->workDays,
                    'monthly_salary' => $dto->monthlySalary,
                ], static fn ($value) => $value !== null);

                $secretary->update($secretaryPayload);
            }

            return $secretary->fresh()->load('user');
        });
    }

    public function delete(Secretary $secretary): void
    {
        DB::transaction(function () use ($secretary) {
            $secretary->user()->update(['user_status' => UserStatus::SUSPENDED->value]);
            $secretary->delete();
        });
    }
}
