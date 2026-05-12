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
        $consultation = $action->execute($appointment, StoreConsultationDTO::fromRequest($request));

        return $this->ok('Consultation saved successfully', new ConsultationResource($consultation));
    }
}
