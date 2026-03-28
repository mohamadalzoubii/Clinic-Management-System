<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'max:12', 'string'],
            'last_name' => ['required', 'max:12', 'string'],
            'email' => ['required', 'string', 'max:40', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'min:8', 'confirmed']
        ];
    }
}
