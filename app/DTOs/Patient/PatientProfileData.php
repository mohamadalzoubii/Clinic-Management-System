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
