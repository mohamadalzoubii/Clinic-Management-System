<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConsultationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'consultation',
            'id' => (string) $this->id,

            'attributes' => [

                'appointment_id' => $this->appointment_id,
                'notes' => $this->notes,
                'next_visit_date' => $this->next_visit_date,
                'created_at' => $this->created_at->format('Y-m-d H:i A'),
            ],

            'relationships' => [
                'medicines' => $this->whenLoaded('prescriptionItems', function () {
                    return $this->prescriptionItems->map(fn ($item) => [
                        'name' => $item->medicine_name,
                        'dosage' => $item->dosage,
                        'frequency' => $item->frequency,
                        'duration' => $item->duration,
                        'item_notes' => $item->notes,
                    ]);
                }),
                'patient_attachments' => $this->whenLoaded('appointment', function () {
                    return $this->appointment->attachments->map(fn ($file) => [
                        'id' => $file->id,
                        'name' => $file->file_name,
                        'type' => $file->mime_type,
                        'url' => asset('storage/'.$file->file_path),
                    ]);
                }),
            ],

            'links' => [
                //                'self' => route('consultations.show', $this->id),
            ],
        ];
    }
}
