<?php

namespace App\DTOs\Patient;

use App\Enums\Medical\BloodType;
use App\Enums\Medical\Gender;
use App\Http\Requests\Admin\UpdatePatientRequest;
use Illuminate\Http\Request;

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

        return new self(
            firstName: $validated['first_name'] ?? null,
            lastName: $validated['last_name'] ?? null,
            email: $validated['email'] ?? null,
            phone: $validated['phone'] ?? null,
            password: $validated['password'] ?? null,
            dateOfBirth: $validated['date_of_birth'] ?? null,
            emergencyContactName: $validated['emergency_contact_name'] ?? null,
            emergencyContactPhone: $validated['emergency_contact_phone'] ?? null,
            allergies: $validated['allergies'] ?? null,
            chronicDiseases: $validated['chronic_diseases'] ?? null,
            weight: isset($validated['weight']) ? (float) $validated['weight'] : null,
            height: isset($validated['height']) ? (float) $validated['height'] : null,
            gender: isset($validated['gender']) ? Gender::from($validated['gender']) : null,
            bloodType: isset($validated['blood_type']) ? BloodType::from($validated['blood_type']) : null,
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
