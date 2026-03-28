<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'user',
            'id' => (string) $this->id,

            'attributes' =>[
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                
            ],

            'includes' => [],

            'links' => [],

        ];
    }
}
