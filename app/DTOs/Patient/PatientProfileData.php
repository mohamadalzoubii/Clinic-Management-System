<?php

namespace App\DTOs\Patient;

use Illuminate\Http\Request;

class PatientProfileData
{
    public function __construct(
        public ?string $emergency_contact_name,
        public ?string $emergency_contact_phone,
        public ?string $allergies,
        public ?string $chronic_diseases,
        public ?int $weight,
        public ?int $height,
        public string $gender,
        public string $blood_type,
        public string $date_of_birth,

    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            emergency_contact_name: $data['emergency_contact_name'] ?? null,
            emergency_contact_phone: $data['emergency_contact_phone'] ?? null,
            allergies: $data['allergies'] ?? null,
            chronic_diseases: $data['chronic_diseases'] ?? null,
            weight: isset($data['weight']) ? (float) $data['weight'] : null,
            height: isset($data['height']) ? (float) $data['height'] : null,
            gender: $data['gender'] ?? null,
            blood_type: $data['blood_type'] ?? null,
            date_of_birth: $data['date_of_birth'] ?? null,
        );
    }

    public static function fromRequest(Request $request): self
    {
        return new self(
            $request->validated('emergency_contact_name'),
            $request->validated('emergency_contact_phone'),
            $request->validated('allergies'),
            $request->validated('chronic_diseases'),
            $request->validated('weight'),
            $request->validated('height'),
            $request->validated('gender'),
            $request->validated('blood_type'),
            $request->validated('date_of_birth'),
        );
    }
}
