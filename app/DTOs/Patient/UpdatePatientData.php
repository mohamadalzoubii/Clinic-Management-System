<?php

namespace App\DTOs\Patient;

use App\Enums\Medical\BloodType;
use App\Enums\Medical\Gender;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

readonly class UpdatePatientData
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?string $phone,
        public ?string $password,
        public ?string $dateOfBirth,
        public ?string $emergencyContactName,
        public ?string $emergencyContactPhone,
        public ?string $allergies,
        public ?string $chronicDiseases,
        public ?float $weight,
        public ?float $height,
        public ?Gender $gender,
        public ?BloodType $bloodType,
    ) {}

    public static function formRequest(Request $request): self
    {
        $validated = $request->validated();

        $emergencyFullName = null;
        $emFirstName = Arr::get($validated, 'emergency_contact.first_name');
        $emLastName = Arr::get($validated, 'emergency_contact.last_name');

        if ($emFirstName || $emLastName) {
            $emergencyFullName = trim($emFirstName.' '.$emLastName);
        }

        return new self(
            firstName: Arr::get($validated, 'personal_data.first_name'),
            lastName: Arr::get($validated, 'personal_data.last_name'),
            email: Arr::get($validated, 'personal_data.email'),
            phone: Arr::get($validated, 'personal_data.phone_number'),
            password: Arr::get($validated, 'personal_data.password'),
            dateOfBirth: Arr::get($validated, 'personal_data.date_of_birth'),
            emergencyContactName: $emergencyFullName ?: null,
            emergencyContactPhone: Arr::get($validated, 'emergency_contact.phone_number'),
            allergies: Arr::get($validated, 'health_assessment.allergies'),
            chronicDiseases: Arr::get($validated, 'health_assessment.chronic_condition'),
            weight: Arr::has($validated, 'health_assessment.weight') ? (float) Arr::get($validated,
                'health_assessment.weight') : null,
            height: Arr::has($validated, 'health_assessment.height') ? (float) Arr::get($validated,
                'health_assessment.height') : null,
            gender: Arr::has($validated, 'personal_data.gender') ? Gender::from(Arr::get($validated,
                'personal_data.gender')) : null,
            bloodType: Arr::has($validated, 'health_assessment.blood_type') ? BloodType::from(Arr::get($validated,
                'health_assessment.blood_type')) : null,
        );
    }

    public function hasUserChanges(): bool
    {
        return $this->firstName !== null
            || $this->lastName !== null
            || $this->email !== null
            || $this->phone !== null
            || $this->password !== null;
    }

    public function hasPatientChanges(): bool
    {
        return $this->dateOfBirth !== null
            || $this->emergencyContactName !== null
            || $this->emergencyContactPhone !== null
            || $this->allergies !== null
            || $this->chronicDiseases !== null
            || $this->weight !== null
            || $this->height !== null
            || $this->gender !== null
            || $this->bloodType !== null;
    }
}
