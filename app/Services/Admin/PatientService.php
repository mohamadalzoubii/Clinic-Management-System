<?php

namespace App\Services\Admin;

use App\DTOs\Patient\StorePatientData;
use App\DTOs\Patient\UpdatePatientData;
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
    public function __construct(private readonly FinancialService $financialService)
    {
    }

    public function store(StorePatientData $dto): Patient
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

            $user->assignRole(RoleEnum::PATIENT->value);

            return $user->patient()->create([
                'date_of_birth' => $dto->dateOfBirth,
                'city' => $dto->city ?? null,
                'emergency_contact_name' => $dto->emergencyContactName,
                'emergency_contact_phone' => $dto->emergencyContactPhone,
                'emergency_contact_relationship' => $dto->emergencyContactRelationship ?? null,
                'emergency_contact_email' => $dto->emergencyContactEmail ?? null,
                'emergency_contact_city' => $dto->emergencyContactCity ?? null,
                'allergies' => $dto->allergies,
                'chronic_diseases' => $dto->chronicDiseases,
                'weight' => $dto->weight,
                'height' => $dto->height,
                'gender' => $dto->gender?->value,
                'blood_type' => $dto->bloodType?->value,
                'is_smoker' => $dto->isSmoker ?? false,
                'drinks_alcohol' => $dto->drinksAlcohol ?? false,
                'smoking_status' => $dto->smokingStatus ?? null,
                'alcohol_status' => $dto->alcoholStatus ?? null,
            ]);
        });
    }

    public function delete(Patient $patient): void
    {
        DB::transaction(function () use ($patient) {
            $patient->user()->update(['user_status' => UserStatus::SUSPENDED->value]);
            $patient->delete();
        });
    }

    public function update(Patient $patient, UpdatePatientData $dto): Patient
    {
        return DB::transaction(function () use ($patient, $dto) {
            if ($dto->hasUserChanges()) {
                $userPayload = array_filter([
                    'first_name' => $dto->firstName,
                    'last_name' => $dto->lastName,
                    'email' => $dto->email,
                    'phone' => $dto->phone,
                    'password' => $dto->password !== null ? Hash::make($dto->password) : null,
                ], static fn($value) => $value !== null);

                $patient->user->update($userPayload);
            }

            if ($dto->hasPatientChanges()) {
                $patientPayload = array_filter([
                    'date_of_birth' => $dto->dateOfBirth,
                    'city' => $dto->city,
                    'emergency_contact_name' => $dto->emergencyContactName,
                    'emergency_contact_phone' => $dto->emergencyContactPhone,
                    'emergency_contact_relationship' => $dto->emergencyContactRelationship,
                    'emergency_contact_email' => $dto->emergencyContactEmail,
                    'emergency_contact_city' => $dto->emergencyContactCity,
                    'allergies' => $dto->allergies,
                    'chronic_diseases' => $dto->chronicDiseases,
                    'weight' => $dto->weight,
                    'height' => $dto->height,
                    'gender' => $dto->gender?->value,
                    'blood_type' => $dto->bloodType?->value,
                    'is_smoker' => $dto->isSmoker,
                    'drinks_alcohol' => $dto->drinksAlcohol,
                    'smoking_status' => $dto->smokingStatus,
                    'alcohol_status' => $dto->alcoholStatus,
                ], static fn($value) => $value !== null);

                $patient->update($patientPayload);
            }

            return $patient->fresh()->load('user');
        });
    }

    public function buyPackageForPatient(User $user, Package $package): array
    {
        return $this->financialService->buyPackageForUser($user, $package);
    }
}
