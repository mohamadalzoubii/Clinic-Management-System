<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

class ConsultationResource extends JsonResource
{
    private function normalizeDateValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function toArray(Request $request): array
    {
        return [
            'type' => 'consultation',
            'id' => (string) $this->id,
            'attributes' => [
                'appointment_id' => $this->appointment_id,
                'anamnesis' => $this->anamnesis,
                'symptoms' => $this->symptoms,
                'diagnosis' => $this->diagnosis,
                'next_visit_date' => $this->normalizeDateValue($this->next_visit_date),
                'created_at' => $this->created_at?->format('Y-m-d H:i A'),
            ],
            'relationships' => [
                'medicines' => $this->whenLoaded('prescriptionItems', function () {
                    return $this->prescriptionItems->map(fn ($item) => [
                        'name' => $item->medicine_name,
                        'category' => $item->category,
                        'dosage' => $item->dosage,
                        'form_and_quantity' => $item->form_and_quantity,
                        'frequency' => $item->frequency,
                        'duration' => $item->duration,
                        'details' => [
                            'special_instructions' => $item->special_instructions,
                            'storage' => $item->storage_instructions,
                            'side_effects' => $item->side_effects,
                            'allergy_warning' => $item->allergy_warnings,
                        ],
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
        ];
    }
}
