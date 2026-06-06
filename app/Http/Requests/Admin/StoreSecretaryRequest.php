<?php

namespace App\Http\Requests\Admin;

use App\Models\Secretary;
use Illuminate\Foundation\Http\FormRequest;

class StoreSecretaryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Secretary::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'work_days' => ['nullable', 'string', 'max:255'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
