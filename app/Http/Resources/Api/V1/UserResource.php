<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\Admin\PatientResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// تأكد من المسار حسب مشروعك

// تأكد من المسار حسب مشروعك

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
                'phone' => $this->phone,
                'status' => $this->user_status,
                'wallet_balance' => $this->wallet_balance,
                'roles' => $this->getRoleNames()->values(),

                'is_profile_completed' => (bool) $this->patient?->date_of_birth,
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
