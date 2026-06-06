<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var Invoice $invoice */
        $invoice = $this->resource;
        $patient = $invoice->user?->patient;
        $patientUser = $patient?->user;

        return [
            'id' => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
            'amount' => $invoice->amount,
            'status' => $invoice->status?->value ?? $invoice->status,
            'entry_type' => $invoice->entry_type,
            'paid_at' => $invoice->paid_at?->toISOString(),
            'appointment_id' => $invoice->appointment_id,
            'download_url' => url("/api/v1/invoices/{$invoice->id}/download"),
            'patient' => $patient ? [
                'id' => $patient->id,
                'first_name' => $patientUser?->first_name,
                'last_name' => $patientUser?->last_name,
                'email' => $patientUser?->email,
            ] : null,
            'created_at' => $invoice->created_at?->toISOString(),
        ];
    }
}
