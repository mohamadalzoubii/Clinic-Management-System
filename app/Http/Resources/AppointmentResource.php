<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\V1\DoctorResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'type' => 'appointment',

            'id' => (string) $this->id,

            'attributes' => [
                'doctor_id' => $this->doctor_id,
                'appointment_date' => $this->appointment_date,
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'status' => $this->status,
                'reason' => $this->reason,
            ],

            'patient_attachments' => $this->attachments->map(fn ($file) => [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'file_url' => asset('storage/'.$file->file_path),
                'mime_type' => $file->mime_type,
            ]),

            'relationships' => [
                'doctor' => $this->whenLoaded('doctor', function () {
                    new DoctorResource($this->whenLoaded('doctor'));
                }),
                'patient' => new PatientResource($this->whenLoaded('patient')),
            ],

        ];
    }
}
