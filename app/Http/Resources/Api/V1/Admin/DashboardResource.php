<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'totals' => $this->resource['totals'] ?? [],
            'revenue' => $this->resource['revenue'] ?? 0,
            'focus' => $this->resource['focus'] ?? [],
            'recent_doctors' => DoctorResource::collection($this->resource['recent_doctors'] ?? [])->resolve(),
            'top_patients' => PatientResource::collection($this->resource['top_patients'] ?? [])->resolve(),
            'packages' => PackageResource::collection($this->resource['packages'] ?? [])->resolve(),
        ];
    }
}