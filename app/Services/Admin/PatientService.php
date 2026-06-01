<?php

namespace App\Services\Admin;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Package;
use App\Models\Patient;
use App\Models\User;
use App\Services\FinancialService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PatientService
{
    public function __construct(private readonly FinancialService $financialService) {}

    public function store(array $data): Patient
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

            $user->assignRole(RoleEnum::PATIENT->value);

            return $user->patient()->create([
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'chronic_diseases' => $data['chronic_diseases'] ?? null,
                'weight' => $data['weight'] ?? null,
                'height' => $data['height'] ?? null,
                'gender' => $data['gender'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
            ]);
        });
    }

    public function update(Patient $patient, array $data): Patient
    {
        return DB::transaction(function () use ($patient, $data) {
            $patient->user->update(array_filter([
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => isset($data['password']) ? Hash::make($data['password']) : null,
            ], static fn ($value) => $value !== null));

            $patient->update(array_filter([
                'date_of_birth' => $data['date_of_birth'] ?? null,
                'emergency_contact_name' => $data['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $data['emergency_contact_phone'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'chronic_diseases' => $data['chronic_diseases'] ?? null,
                'weight' => $data['weight'] ?? null,
                'height' => $data['height'] ?? null,
                'gender' => $data['gender'] ?? null,
                'blood_type' => $data['blood_type'] ?? null,
            ], static fn ($value) => $value !== null));

            return $patient->fresh()->load('user');
        });
    }

    public function delete(Patient $patient): void
    {
        DB::transaction(function () use ($patient) {
            $patient->user()->update(['user_status' => UserStatus::SUSPENDED->value]);
            $patient->delete();
        });
    }

    public function buyPackageForPatient(User $user, Package $package): array
    {
        return $this->financialService->buyPackageForUser($user, $package);
    }
}