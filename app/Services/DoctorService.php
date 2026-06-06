<?php

namespace App\Services;

use App\Actions\Appointment\CancelAppointmentAction;
use App\DTOs\Doctor\StoreDoctorData;
use App\DTOs\Doctor\UpdateDoctorData;
use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DoctorService
{
    public function __construct(private readonly CancelAppointmentAction $cancelAppointmentAction) {}

    public function store(StoreDoctorData $dto): Doctor
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

            $user->assignRole(RoleEnum::DOCTOR->value);

            return $user->doctor()->create([
                'specialization' => $dto->specialization,
                'bio' => $dto->bio,
                'education' => $dto->education,
                'certification' => $dto->certification,
                'years_of_experience' => $dto->yearsOfExperience,
                'session_price' => $dto->sessionPrice,
                'license_number' => $dto->licenseNumber,
                'gender' => $dto->gender->value,
            ]);
        });
    }

    public function update(Doctor $doctor, UpdateDoctorData $dto): Doctor
    {
        return DB::transaction(function () use ($doctor, $dto) {
            if ($dto->hasUserChanges()) {
                $userPayload = array_filter([
                    'first_name' => $dto->firstName,
                    'last_name' => $dto->lastName,
                    'email' => $dto->email,
                    'phone' => $dto->phone,
                    'password' => $dto->password !== null ? Hash::make($dto->password) : null,
                ], static fn ($value) => $value !== null);

                $doctor->user->update($userPayload);
            }

            if ($dto->hasDoctorChanges()) {
                $doctorPayload = array_filter([
                    'specialization' => $dto->specialization,
                    'bio' => $dto->bio,
                    'education' => $dto->education,
                    'certification' => $dto->certification,
                    'years_of_experience' => $dto->yearsOfExperience,
                    'session_price' => $dto->sessionPrice,
                    'license_number' => $dto->licenseNumber,
                    'gender' => $dto->gender?->value,
                ], static fn ($value) => $value !== null);

                $doctor->update($doctorPayload);
            }

            return $doctor->fresh()->load('user');
        });
    }

    public function delete(Doctor $doctor): void
    {
        DB::transaction(function () use ($doctor) {
            $pendingAppointments = $doctor->appointments()
                ->pending()
                ->lockForUpdate()
                ->get();

            foreach ($pendingAppointments as $appointment) {
                $this->cancelAppointmentAction->executeForDoctor($appointment, $doctor->id);
            }

            $doctor->user()->update(['user_status' => UserStatus::SUSPENDED->value]);
            $doctor->delete();
        });
    }
}
