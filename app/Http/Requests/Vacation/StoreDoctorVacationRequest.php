<?php

namespace App\Http\Requests\Vacation;

use Illuminate\Foundation\Http\FormRequest;

class StoreDoctorVacationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->doctor()->exists();
    }

    public function rules(): array
    {
        return [
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
        ];
    }
}