<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Medical\Consutation\StoreConsultationAction;
use App\DTOs\Consultation\StoreConsultationDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Consultation\StoreConsultationRequest;
use App\Http\Resources\Api\V1\ConsultationResource;
use App\Models\Appointment;
use App\Traits\ApiResponses;

class ConsultationController extends Controller
{
    use ApiResponses;

    public function storeConsultation(
        StoreConsultationRequest $request,
        Appointment $appointment,
        StoreConsultationAction $action
    ) {
        $result = $action->execute($appointment, StoreConsultationDTO::fromRequest($request));

        return $this->ok('Consultation saved successfully', [
            'consultation' => new ConsultationResource($result['consultation']),
            'payout_invoices' => collect($result['payout_invoices'])->map(fn ($invoice) => [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'status' => $invoice->status?->value ?? $invoice->status,
                'entry_type' => $invoice->entry_type,
                'download_url' => url("/api/v1/invoices/{$invoice->id}/download"),
            ])->values(),
            'current_balance' => $result['current_balance'],
        ]);
    }
}
