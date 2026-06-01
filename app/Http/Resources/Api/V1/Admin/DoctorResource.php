<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\VacationService;
use Carbon\Carbon;

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
                'gender' => $this->gender,
                'license_number' => $this->license_number,
                'bio' => $this->bio,
                'session_price' => $this->session_price,
                'wallet_balance' => $this->whenLoaded('user') ? $this->user->wallet_balance : null,
                'status' => $this->whenLoaded('user') ? $this->user->user_status : null,
                'appointments_count' => $this->appointments_count ?? null,
                'rating_average' => $this->rating_average ?? null,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
                'on_leave' => app(VacationService::class)->isBlockingDate($this->id, Carbon::today()),
            ],
            'relationships' => [
                'user' => $this->whenLoaded('user'),
            ],
        ];
    }
}