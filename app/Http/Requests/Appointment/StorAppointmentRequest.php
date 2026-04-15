<?php

namespace App\Http\Requests\Appointment;

use Illuminate\Foundation\Http\FormRequest;

class StorAppointmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->patient()->exists();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'doctor_id' => ['required', 'exists:doctors,id'],
            'date' => ['required', 'date_format:Y-m-d', 'date_equals:today_or_after'],
            'time' => ['required', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:500'],

            'files' => ['nullable', 'array', 'max:5'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ];
    }
}
