<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SecretaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'secretary',
            'id' => (string) $this->id,
            'attributes' => [
                'work_days' => $this->work_days,
                'monthly_salary' => $this->monthly_salary,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
            'relationships' => [
                'user' => $this->whenLoaded('user'),
            ],
        ];
    }
}