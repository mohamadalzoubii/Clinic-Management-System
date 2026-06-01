<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'patient',
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
                'wallet_balance' => $this->whenLoaded('user') ? $this->user->wallet_balance : null,
                'status' => $this->whenLoaded('user') ? $this->user->user_status : null,
                'appointments_count' => $this->appointments_count ?? null,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
            'relationships' => [
                'user' => $this->whenLoaded('user'),
            ],
        ];
    }
}