<?php

namespace App\Http\Requests\Api\V1\Patient;

use Illuminate\Foundation\Http\FormRequest;

class PatientProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'personal_data' => ['required', 'array'],
            'personal_data.first_name' => ['required', 'string'],
            'personal_data.last_name' => ['required', 'string'],
            'personal_data.date_of_birth' => ['required', 'date'],
            'personal_data.phone_number' => ['required', 'string'],
            'personal_data.city' => ['nullable', 'string'],
            'emergency_contact' => ['required', 'array'],
            'emergency_contact.first_name' => ['required', 'string'],
            'emergency_contact.last_name' => ['required', 'string'],
            'emergency_contact.relationship' => ['nullable', 'string'],
            'emergency_contact.phone_number' => ['required', 'string'],
            'emergency_contact.email' => ['nullable', 'email'],
            'emergency_contact.city' => ['nullable', 'string'],
            'health_assessment' => ['required', 'array'],
            'health_assessment.blood_type' => ['nullable', 'string'],
            'health_assessment.allergies' => ['nullable', 'string'],
            'health_assessment.chronic_condition' => ['nullable', 'string'],
            'health_assessment.height' => ['nullable', 'numeric'],
            'health_assessment.weight' => ['nullable', 'numeric'],
            'life_style' => ['required', 'array'],
            'life_style.smoking' => ['nullable', 'string'],
            'life_style.alcohol' => ['nullable', 'string'],
        ];
    }
}
