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
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$userId],
            'phone' => ['nullable', 'sometimes', 'string', 'max:20', 'unique:users,phone,'.$userId],
            'password' => ['sometimes', 'string', 'min:8'],
            'date_of_birth' => ['nullable', 'sometimes', 'date'],
            'emergency_contact_name' => ['nullable', 'sometimes', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'sometimes', 'string', 'max:20'],
            'allergies' => ['nullable', 'sometimes', 'string'],
            'chronic_diseases' => ['nullable', 'sometimes', 'string'],
            'weight' => ['nullable', 'sometimes', 'numeric', 'min:0'],
            'height' => ['nullable', 'sometimes', 'numeric', 'min:0'],
            'gender' => ['nullable', 'sometimes', 'string', Rule::enum(Gender::class)],
            'blood_type' => ['nullable', 'sometimes', 'string', Rule::enum(BloodType::class)],
        ];
    }
}
