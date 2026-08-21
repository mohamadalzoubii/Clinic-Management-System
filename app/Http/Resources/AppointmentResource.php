<?php

namespace App\Http\Resources;

use App\Http\Resources\Api\V1\ConsultationResource;
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
                'patient_id' => $this->patient_id,
                'doctor_id' => $this->doctor_id,
                'appointment_date' => $this->appointment_date?->format('Y-m-d'),
                'start_time' => $this->start_time,
                'end_time' => $this->end_time,
                'status' => $this->status,
                'reason' => $this->reason,
                'notes' => $this->notes,
                'reminder_sent' => $this->reminder_sent,
                'rescheduled' => $this->rescheduled,
                'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            ],

            'financial' => $this->whenLoaded('invoices', function () {
                return [
                    'invoices' => $this->invoices->map(fn ($invoice) => [
                        'id' => $invoice->id,
                        'invoice_number' => $invoice->invoice_number,
                        'amount' => $invoice->amount,
                        'status' => $invoice->status?->value ?? $invoice->status,
                        'entry_type' => $invoice->entry_type,
                        'paid_at' => $invoice->paid_at?->toISOString(),
                        'download_url' => url("/api/v1/invoices/{$invoice->id}/download"),
                    ])->values(),
                ];
            }),

            'patient_attachments' => $this->attachments->map(fn ($file) => [
                'id' => $file->id,
                'file_name' => $file->file_name,
                'file_url' => asset('storage/'.$file->file_path),
                'mime_type' => $file->mime_type,
            ]),

            'relationships' => [
                'doctor' => $this->whenLoaded('doctor', function () {
                    return new DoctorResource($this->whenLoaded('doctor'));
                }),
                'patient' => new PatientResource($this->whenLoaded('patient')),
                'consultation' => $this->whenLoaded('consultation', function () {
                    return new ConsultationResource($this->whenLoaded('consultation'));
                }),
            ],

        ];
    }
}
