<?php

namespace App\Services\Admin;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorService
{
    public function store(array $data): Doctor
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

            $user->assignRole(RoleEnum::DOCTOR->value);

            return $user->doctor()->create([
                'specialization' => $data['specialization'],
                'bio' => $data['bio'],
                'education' => $data['education'],
                'certification' => $data['certification'],
                'years_of_experience' => $data['years_of_experience'],
                'session_price' => $data['session_price'],
                'license_number' => $data['license_number'],
                'gender' => $data['gender'],
            ]);
        });
    }

    public function update(Doctor $doctor, array $data): Doctor
    {
        return DB::transaction(function () use ($doctor, $data) {
            $doctor->user->update(array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
            ], static fn ($value) => $value !== null));

            $doctor->update(array_filter([
                'specialization' => $data['specialization'] ?? null,
                'bio' => $data['bio'] ?? null,
                'education' => $data['education'] ?? null,
                'certification' => $data['certification'] ?? null,
                'years_of_experience' => $data['years_of_experience'] ?? null,
                'session_price' => $data['session_price'] ?? null,
                'license_number' => $data['license_number'] ?? null,
                'gender' => $data['gender'] ?? null,
            ], static fn ($value) => $value !== null));

            return $doctor->fresh()->load('user');
        });
    }

    public function delete(Doctor $doctor): void
    {
        DB::transaction(function () use ($doctor) {
            $doctor->user()->update(['user_status' => UserStatus::SUSPENDED->value]);
            $doctor->delete();
        });
    }
}