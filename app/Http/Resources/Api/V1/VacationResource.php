<?php

namespace App\Http\Resources\Api\V1;

use App\Http\Resources\Api\V1\DoctorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VacationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'vacation',
            'id' => (string) $this->id,
            'attributes' => [
                'doctor_id' => $this->doctor_id,
                'start_date' => $this->start_date?->format('Y-m-d'),
                'end_date' => $this->end_date?->format('Y-m-d'),
                'status' => $this->status?->value ?? $this->status,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
            'relationships' => [
                'doctor' => $this->whenLoaded('doctor', function () {
                    return new DoctorResource($this->doctor->loadMissing('user'));
                }),
            ],
        ];
    }
}