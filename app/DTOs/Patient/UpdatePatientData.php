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
        public ?bool $isSmoker,
        public ?bool $drinksAlcohol,
        public ?string $smokingStatus,
        public ?string $alcoholStatus,
    ) {}

    public static function formRequest(Request $request): self
    {
        $validated = $request->validated();
        $smokingStatus = Arr::get($validated, 'smoking_status');
        $alcoholStatus = Arr::get($validated, 'alcohol_status');

        return new self(
            firstName: Arr::get($validated, 'first_name'),
            lastName: Arr::get($validated, 'last_name'),
            email: Arr::get($validated, 'email'),
            phone: Arr::get($validated, 'phone'),
            password: Arr::get($validated, 'password'),
            dateOfBirth: Arr::get($validated, 'date_of_birth'),
            city: Arr::get($validated, 'city'),
            emergencyContactName: Arr::get($validated, 'emergency_contact_name'),
            emergencyContactPhone: Arr::get($validated, 'emergency_contact_phone'),
            emergencyContactRelationship: Arr::get($validated, 'emergency_contact_relationship'),
            emergencyContactEmail: Arr::get($validated, 'emergency_contact_email'),
            emergencyContactCity: Arr::get($validated, 'emergency_contact_city'),
            allergies: Arr::get($validated, 'allergies'),
            chronicDiseases: Arr::get($validated, 'chronic_diseases'),
            weight: Arr::has($validated, 'weight') ? (float) Arr::get($validated,
                'weight') : null,
            height: Arr::has($validated, 'height') ? (float) Arr::get($validated,
                'height') : null,
            gender: Arr::has($validated, 'gender') ? Gender::from(Arr::get($validated,
                'gender')) : null,
            bloodType: Arr::has($validated, 'blood_type') ? BloodType::from(Arr::get($validated,
                'blood_type')) : null,
            isSmoker: $smokingStatus !== null && strtolower($smokingStatus) !== 'no',
            drinksAlcohol: $alcoholStatus !== null && strtolower($alcoholStatus) !== 'no',
            smokingStatus: $smokingStatus,
            alcoholStatus: $alcoholStatus,
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
            || $this->city !== null
            || $this->emergencyContactName !== null
            || $this->emergencyContactPhone !== null
            || $this->emergencyContactRelationship !== null
            || $this->emergencyContactEmail !== null
            || $this->emergencyContactCity !== null
            || $this->allergies !== null
            || $this->chronicDiseases !== null
            || $this->weight !== null
            || $this->height !== null
            || $this->gender !== null
            || $this->bloodType !== null
            || $this->smokingStatus !== null
            || $this->alcoholStatus !== null;
    }
}
