<?php

namespace App\Http\Requests\Api\V1\Patient;

use App\Models\Patient;
use Illuminate\Foundation\Http\FormRequest;

class PatientProfileUpdateRequest extends FormRequest
{
    public function rules()
    {
        return [
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'allergies' => ['nullable', 'string'],
            'chronic_diseases' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:1'],
            'height' => ['nullable', 'numeric', 'min:1'],
            'gender' => ['nullable', 'in:male,female'],
            'blood_type' => ['nullable', 'string', 'max:5'],
            'date_of_birth' => ['nullable', 'date'],
        ];
    }

    public function authorize(): bool
    {
        $patientId = $this->route('patient');
        $patient = Patient::findOrFail($patientId);

        return $this->user()->can('update', $patient);
    }
}
