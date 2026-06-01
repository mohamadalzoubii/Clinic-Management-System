<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $reviewerName = trim(sprintf(
            '%s %s',
            $this->patient?->user?->first_name ?? '',
            $this->patient?->user?->last_name ?? '',
        ));

        return [
            'type' => 'doctor_review',
            'id' => (string) $this->id,
            'attributes' => [
                'rating' => $this->rating,
                'comment' => $this->comment,
                'reviewer_name' => $reviewerName !== '' ? $reviewerName : null,
                'created_at' => $this->created_at?->toISOString(),
                'updated_at' => $this->updated_at?->toISOString(),
            ],
        ];
    }
}