<?php

namespace App\Http\Requests\Api\V1\Consultation;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'max:5000'],
            'next_visit_date' => ['nullable', 'date', 'after:today'],

            'medicines' => ['nullable', 'array', 'min:1'],
            'medicines.*.name' => ['required_with:medicines', 'string', 'max:255'],
            'medicines.*.dosage' => ['required_with:medicines', 'string', 'max:255'],
            'medicines.*.frequency' => ['required_with:medicines', 'string', 'max:255'],
            'medicines.*.duration' => ['required_with:medicines', 'string', 'max:255'],
            'medicines.*.item_notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
