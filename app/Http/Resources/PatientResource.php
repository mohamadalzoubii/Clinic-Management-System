<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PatientResource extends JsonResource
{
    /* 
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'patient',
            'id' => (string) $this->id,
            'attributes' => [
                'personal_data' => [
                    'first_name' => $this->whenLoaded('user') ? $this->user->first_name : null,
                    'last_name' => $this->whenLoaded('user') ? $this->user->last_name : null,
                    'date_of_birth' => $this->date_of_birth?->format('Y-m-d') ?? $this->date_of_birth,
                    'phone_number' => $this->whenLoaded('user') ? $this->user->phone : null,
                    'city' => $this->city,
                    'gender' => $this->gender?->value ?? $this->gender,
                ],
                'emergency_contact' => [
                    'name' => $this->emergency_contact_name,
                    'relationship' => $this->emergency_contact_relationship ?? null,
                    'phone_number' => $this->emergency_contact_phone,
                    'email' => $this->emergency_contact_email ?? null,
                    'city' => $this->emergency_contact_city ?? null,
                ],
                'health_assessment' => [
                    'blood_type' => $this->blood_type?->value ?? $this->blood_type,
                    'allergies' => $this->allergies,
                    'chronic_condition' => $this->chronic_diseases,
                    'height' => $this->height,
                    'weight' => $this->weight,
                ],
                'life_style' => [
                    'is_smoker' => (bool) $this->is_smoker,
                    'drinks_alcohol' => (bool) $this->drinks_alcohol,
                    'smoking' => $this->smoking_status ?? null,
                    'alcohol' => $this->alcohol_status ?? null,
                ],
                'media' => [
                    'xrays' => $this->formatMediaCollection('xray_images'),
                    'medical_tests' => $this->formatMediaCollection('medical_test_images'),
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
            'links' => [
                'self' => url('/api/v1/patients/'.$this->id),
            ],
        ];
    }

    /*
     * @return array<int, array<string, mixed>>
     */
    private function formatMediaCollection(string $collectionName): array
    {
        if (! method_exists($this->resource, 'getMedia')) {
            return [];
        }

        /** @var Collection<int, Media> $media */
        $media = $this->resource->getMedia($collectionName);

        return $media->map(function (Media $m) {
            return [
                'id' => (string) $m->id,
                'url' => $m->getUrl(),
                'name' => $m->name,
                'file_name' => $m->file_name,
                'mime_type' => $m->mime_type,
                'created_at' => $m->created_at?->toISOString(),
            ];
        })->values()->toArray();
    }
}