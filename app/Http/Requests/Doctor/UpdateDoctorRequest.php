<?php

namespace App\Http\Requests\Doctor;

use App\Enums\Medical\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        $doctor = $this->route('doctor');

        return $this->user()?->can('update', $doctor) ?? false;
    }

    public function rules(): array
    {
        $doctor = $this->route('doctor');
        $userId = $doctor?->user_id;
        $doctorId = $doctor?->id;

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$userId],
            'phone' => ['nullable', 'sometimes', 'string', 'max:20', 'unique:users,phone,'.$userId],
            'password' => ['sometimes', 'string', 'min:8'],
            'specialization' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'string'],
            'education' => ['sometimes', 'string', 'max:255'],
            'certification' => ['sometimes', 'string', 'max:255'],
            'years_of_experience' => ['sometimes', 'integer', 'min:0'],
            'session_price' => ['sometimes', 'numeric', 'min:0'],
            'license_number' => ['sometimes', 'string', 'max:255', 'unique:doctors,license_number,'.$doctorId],
            'gender' => ['sometimes', 'string', Rule::enum(Gender::class)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
