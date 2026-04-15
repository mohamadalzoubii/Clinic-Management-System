<?php

namespace App\Http\Requests\Api\V1\Patient;

use App\Enums\Medical\BloodType;
use App\Enums\Medical\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PatientProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'emergency_contact_name'  => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'phone' => ['nullable', 'string', 'max:10'],
            'allergies'               => ['nullable', 'string'],
            'chronic_diseases'        => ['nullable', 'string'],
            'weight'                  => ['nullable', 'integer', 'min:10', 'max:300'],
            'height'                  => ['nullable', 'integer', 'min:50', 'max:250'],
            'gender'                  => ['required', Rule::enum(Gender::class)],
            'blood_type'              => ['required', Rule::enum(BloodType::class)],
            'date_of_birth'           => ['required', 'date', 'before:today'],
        ];
    }
}
