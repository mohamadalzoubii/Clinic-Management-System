<?php

namespace App\Http\Requests\Secretary;

use Illuminate\Foundation\Http\FormRequest;

class EmergencyBookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'doctor_id' => 'required|integer',
            'date' => 'required|date',
            'start_time' => 'required',
            'patient_id' => 'nullable|integer',
            'patient_name' => 'required_without:patient_id|string',
            'patient_phone' => 'nullable|string',
        ];
    }
}
