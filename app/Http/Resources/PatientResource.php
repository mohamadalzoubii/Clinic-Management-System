<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'patients',

            'id' => (string) $this->id,

            'attributes' => [
                'date_of_birth' => $this->date_of_birth,
                'emergency_contact_name' => $this->emergency_contact_name,
                'emergency_contact_phone' => $this->emergency_contact_phone,
                'gender' => $this->gender,
                'blood_type' => $this->blood_type,

                'medical_details' => [
                    'allergies' => $this->allergies,
                    'chronic_diseases' => $this->chronic_diseases,
                ],

                'physical_stats' => [
                    'weight' => $this->weight,
                    'height' => $this->height,
                ],

                'habits' => [
                    'is_smoker' => $this->is_smoker,
                    'drinks_alcohol' => $this->drinks_alcohol,
                ],
            ],

            'relationships' => [
                'user' => [
                    $this->whenLoaded('user'),
                ],
            ],

            'links' => [
                'self' => url('/api/v1/patients/'.$this->id),
            ],
        ];
    }
}
