<?php

namespace App\Http\Resources\Api\V1;

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
                'average_rating' => $this->reviews_avg_rating ? round($this->reviews_avg_rating, 1) : 0,
        'total_reviews_count' => $this->reviews_count ?? 0,
                // Spatie Media Library: exposed by Doctor model as getDoctorPhotoUrlAttribute()
                'photo_url' => $this->doctor_photo_url ?? null,

                'on_leave' => app(VacationService::class)->isBlockingDate($this->id, Carbon::today()),
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
