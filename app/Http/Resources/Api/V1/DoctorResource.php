<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'doctor',
            'id' => (string) $this->id,

            'attributes' => [
                'specialization' => $this->specialization,
                'education' => $this->education,
                'certification' => $this->certification,
                'years_of_experience' => $this->years_of_experience,
                'license_number' => $this->license_number,
                'bio' => $this->bio,
                'session_price' => $this->session_price,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],

            'relationships' => [
                'reviews' => $this->whenLoaded('reviews'),
                'user' => $this->whenLoaded('user'),
            ],

            'links' => [
                //                'self' => route('doctors.show', $this->id),
            ],
        ];
    }
}
