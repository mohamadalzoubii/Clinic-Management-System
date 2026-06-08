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
        public ?string $city,
        public ?string $emergency_contact_name,
        public ?string $emergency_contact_phone,
        public ?string $emergency_contact_relationship,
        public ?string $emergency_contact_email,
        public ?string $emergency_contact_city,
        public ?string $blood_type,
        public ?string $allergies,
        public ?string $chronic_diseases,
        public ?float $height,
        public ?float $weight,
        public bool $is_smoker,
        public bool $drinks_alcohol,
        public ?string $smoking_status,
        public ?string $alcohol_status,
    ) {}

    public static function fromRequest(Request $request): self
    {
        $smoking = $request->input('life_style.smoking');
        $alcohol = $request->input('life_style.alcohol');

        return new self(
            first_name: $request->input('personal_data.first_name'),
            last_name: $request->input('personal_data.last_name'),
            phone_number: $request->input('personal_data.phone_number'),
            date_of_birth: $request->input('personal_data.date_of_birth'),
            city: $request->input('personal_data.city'),
            emergency_contact_name: trim($request->input('emergency_contact.first_name').' '.$request->input('emergency_contact.last_name')),
            emergency_contact_phone: $request->input('emergency_contact.phone_number'),
            emergency_contact_relationship: $request->input('emergency_contact.relationship'),
            emergency_contact_email: $request->input('emergency_contact.email'),
            emergency_contact_city: $request->input('emergency_contact.city'),
            blood_type: $request->input('health_assessment.blood_type'),
            allergies: $request->input('health_assessment.allergies'),
            chronic_diseases: $request->input('health_assessment.chronic_condition'),
            height: $request->input('health_assessment.height'),
            weight: $request->input('health_assessment.weight'),
            is_smoker: $smoking !== 'no',
            drinks_alcohol: $alcohol !== 'no',
            smoking_status: $smoking,
            alcohol_status: $alcohol,
        );
    }
}
