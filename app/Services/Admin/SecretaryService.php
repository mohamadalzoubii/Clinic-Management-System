<?php

namespace App\Services\Admin;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Secretary;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SecretaryService
{
    public function store(array $data): Secretary
    {
        return DB::transaction(function () use ($data) {
            $user = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => Hash::make($data['password']),
                'user_status' => UserStatus::APPROVED->value,
            ]);

            $user->assignRole(RoleEnum::SECRETARY->value);

            return $user->secretary()->create([
                'work_days' => $data['work_days'] ?? null,
                'monthly_salary' => $data['monthly_salary'] ?? null,
            ]);
        });
    }

    public function update(Secretary $secretary, array $data): Secretary
    {
        return DB::transaction(function () use ($secretary, $data) {
            $secretary->user->update(array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
            ], static fn ($value) => $value !== null));

            $secretary->update(array_filter([
                'work_days' => $data['work_days'] ?? null,
                'monthly_salary' => $data['monthly_salary'] ?? null,
            ], static fn ($value) => $value !== null));

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