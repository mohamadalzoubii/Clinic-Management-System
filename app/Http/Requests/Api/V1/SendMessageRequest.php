<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendMessageRequest extends FormRequest
{
    public function rules(): array
    {
        $receiverTable = $this->user()?->doctor ? 'patients' : 'doctors';

        return [
            'receiverId' => ['required', Rule::exists($receiverTable, 'id')],
            'body' => ['required', 'string', 'max:2000'],
        ];
    }

    public function authorize(): bool
    {
        //        dd(
        //            $this->user()->can('startChat', (int) $this->input('receiver_id')));
        //
        //        return $this->user()->can('startChat', (int) $this->input('receiver_id'));

        return true;
    }
}
