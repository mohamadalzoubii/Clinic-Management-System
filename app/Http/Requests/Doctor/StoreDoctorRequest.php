<?php

namespace App\Http\Requests\Doctor;

use App\Enums\Medical\Gender;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDoctorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Doctor::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'specialization' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string'],
            'education' => ['required', 'string', 'max:255'],
            'certification' => ['required', 'string', 'max:255'],
            'years_of_experience' => ['required', 'integer', 'min:0'],
            'session_price' => ['required', 'numeric', 'min:0'],
            'license_number' => ['required', 'string', 'max:255', 'unique:doctors,license_number'],
            'gender' => ['required', 'string', Rule::enum(Gender::class)],
            'photo' => ['nullable', 'image', 'max:2048'],
        ];
    }
}
