<?php

namespace App\Http\Requests\Admin;

use App\Enums\Medical\AppointmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAppointmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'search' => ['nullable', 'string', 'max:255'],
            'party_id' => ['nullable', 'integer', 'min:1'],
            'party_type' => ['nullable', 'string', Rule::in(['patient', 'doctor'])],
            'status' => ['nullable', 'string', Rule::in(array_column(AppointmentStatus::cases(), 'value'))],
            'date' => ['nullable', 'string', 'max:255'],
            'specialization' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'max:255'],
        ];
    }
}
