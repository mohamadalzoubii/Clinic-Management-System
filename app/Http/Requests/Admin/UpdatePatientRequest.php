<?php

namespace App\Http\Requests\Admin;

use App\Enums\Medical\BloodType;
use App\Enums\Medical\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePatientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('patient')) ?? false;
    }

    public function rules(): array
    {
        $patient = $this->route('patient');
        $userId = $patient?->user_id;

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255'],
            'phone' => ['nullable', 'sometimes', 'string', 'max:20'],
            'password' => ['sometimes', 'string', 'min:8'],
            'date_of_birth' => ['nullable', 'date'],
            'city' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', Rule::enum(Gender::class)],

            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'emergency_contact_relationship' => ['nullable', 'string', 'max:255'],
            'emergency_contact_email' => ['nullable', 'email', 'max:255'],
            'emergency_contact_city' => ['nullable', 'string', 'max:255'],

            'blood_type' => ['nullable', 'string', Rule::enum(BloodType::class)],
            'allergies' => ['nullable', 'string'],
            'chronic_diseases' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],

            'smoking_status' => ['nullable', 'string'],
            'alcohol_status' => ['nullable', 'string'],
        ];
    }
}
