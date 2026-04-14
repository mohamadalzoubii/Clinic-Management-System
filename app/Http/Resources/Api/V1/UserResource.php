<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\PatientResource;
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
            'type' => 'users',

            'id' => (string) $this->id,

            'attributes' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'status' => $this->user_status,
                'created_at' => $this->created_at?->toIso8601String(),
            ],

            'relationships' => [
                'patient' => new PatientResource($this->whenLoaded('patient')),
                'doctor' => new DoctorResource($this->whenLoaded('doctor')),
            ],

            'links' => [

                'self' => url('/api/v1/users/'.$this->id),
            ],
        ];
    }
}
