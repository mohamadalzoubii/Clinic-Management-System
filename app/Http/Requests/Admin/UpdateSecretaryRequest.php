<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSecretaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('secretary')) ?? false;
    }

    public function rules(): array
    {
        $secretary = $this->route('secretary');
        $userId = $secretary?->user_id;

        return [
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$userId],
            'phone' => ['nullable', 'sometimes', 'string', 'max:20', 'unique:users,phone,'.$userId],
            'password' => ['sometimes', 'string', 'min:8'],
            'work_days' => ['nullable', 'sometimes', 'string', 'max:255'],
            'monthly_salary' => ['nullable', 'sometimes', 'numeric', 'min:0'],
        ];
    }
}
