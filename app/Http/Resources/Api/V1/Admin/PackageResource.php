<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'package',
            'id' => (string) $this->id,
            'attributes' => [
                'name' => $this->name,
                'price' => $this->price,
                'balance_amount' => $this->balance_amount,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
        ];
    }
}