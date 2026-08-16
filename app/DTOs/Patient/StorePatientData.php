<?php

namespace App\DTOs\Patient;

use App\Enums\Medical\BloodType;
use App\Enums\Medical\Gender;
use Illuminate\Http\Request;

readonly class StorePatientData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $phone,
        public string $password,
        public ?string $dateOfBirth,
        public ?string $city,
        public ?string $emergencyContactName,
        public ?string $emergencyContactPhone,
        public ?string $emergencyContactRelationship,
        public ?string $emergencyContactEmail,
        public ?string $emergencyContactCity,
        public ?string $allergies,
        public ?string $chronicDiseases,
        public ?float $weight,
        public ?float $height,
        public ?Gender $gender,
        public ?BloodType $bloodType,
        public bool $isSmoker,
        public bool $drinksAlcohol,
        public ?string $smokingStatus,
        public ?string $alcoholStatus,
    ) {}

    public static function formRequest(Request $request): self
    {
        $validated = $request->validated();
        $smoking = $validated['smoking_status'] ?? null;
        $alcohol = $validated['alcohol_status'] ?? null;

        return new self(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            password: $validated['password'],
            dateOfBirth: $validated['date_of_birth'] ?? null,
            city: $validated['city'] ?? null,
            emergencyContactName: $validated['emergency_contact_name'] ?? null,
            emergencyContactPhone: $validated['emergency_contact_phone'] ?? null,
            emergencyContactRelationship: $validated['emergency_contact_relationship'] ?? null,
            emergencyContactEmail: $validated['emergency_contact_email'] ?? null,
            emergencyContactCity: $validated['emergency_contact_city'] ?? null,
            allergies: $validated['allergies'] ?? null,
            chronicDiseases: $validated['chronic_diseases'] ?? null,
            weight: isset($validated['weight']) ? (float) $validated['weight'] : null,
            height: isset($validated['height']) ? (float) $validated['height'] : null,
            gender: isset($validated['gender']) ? Gender::from($validated['gender']) : null,
            bloodType: isset($validated['blood_type']) ? BloodType::from($validated['blood_type']) : null,
            isSmoker: $smoking !== null && strtolower($smoking) !== 'no',
            drinksAlcohol: $alcohol !== null && strtolower($alcohol) !== 'no',
            smokingStatus: $smoking,
            alcoholStatus: $alcohol,
        );
    }
}
