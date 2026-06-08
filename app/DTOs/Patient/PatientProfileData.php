<?php

namespace App\DTOs\Patient;

use Illuminate\Http\Request;

class PatientProfileData
{
    public function __construct(
        public string $first_name,
        public string $last_name,
        public string $phone_number,
        public string $date_of_birth,
        public ?string $emergency_contact_name,
        public ?string $emergency_contact_phone,
        public ?string $blood_type,
        public ?string $allergies,
        public ?string $chronic_diseases,
        public ?float $height,
        public ?float $weight,
        public bool $is_smoker,
        public bool $drinks_alcohol,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            first_name: $request->input('personal_data.first_name'),
            last_name: $request->input('personal_data.last_name'),
            phone_number: $request->input('personal_data.phone_number'),
            date_of_birth: $request->input('personal_data.date_of_birth'),
            emergency_contact_name: trim($request->input('emergency_contact.first_name').' '.$request->input('emergency_contact.last_name')),
            emergency_contact_phone: $request->input('emergency_contact.phone_number'),
            blood_type: $request->input('health_assessment.blood_type'),
            allergies: $request->input('health_assessment.allergies'),
            chronic_diseases: $request->input('health_assessment.chronic_condition'),
            height: $request->input('health_assessment.height'),
            weight: $request->input('health_assessment.weight'),
            is_smoker: $request->input('life_style.smoking') !== 'no',
            drinks_alcohol: $request->input('life_style.alcohol') !== 'no',
        );
    }
}
