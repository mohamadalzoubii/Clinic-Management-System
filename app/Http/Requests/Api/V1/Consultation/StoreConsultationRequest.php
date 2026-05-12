<?php

namespace App\Http\Requests\Api\V1\Consultation;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $appointment = $this->route('appointment');

        return $this->user()->can('addConsultation', $appointment);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'anamnesis' => 'nullable|string',
            'symptoms' => 'nullable|array',
            'symptoms.*' => 'string',
            'diagnosis' => 'nullable|string',
            'next_visit_date' => 'nullable|date',

            'medicines' => 'nullable|array',
            'medicines.*.name' => 'required|string',
            'medicines.*.category' => 'nullable|string',
            'medicines.*.dosage' => 'required|string',
            'medicines.*.form_and_quantity' => 'nullable|string',
            'medicines.*.frequency' => 'required|string',
            'medicines.*.duration' => 'required|string',
            'medicines.*.special_instructions' => 'nullable|string',
            'medicines.*.storage_instructions' => 'nullable|string',
            'medicines.*.side_effects' => 'nullable|string',
            'medicines.*.allergy_warnings' => 'nullable|string',
        ];
    }
}
