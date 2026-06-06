<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ConsultationResource;
use App\Models\Consultation;
use App\Models\Patient;
use App\Traits\ApiResponses;

class PatientHistoryController extends Controller
{
    use ApiResponses;

    public function index(Patient $patient)
    {
        $history = Consultation::query()
            ->where('patient_id', $patient->id)
            ->withFullDetails()
            ->paginate(10);

        return $this->ok('Patient consultation history retrieved successfully.', [
            'history' => ConsultationResource::collection($history)->resolve(),
            'meta' => [
                'current_page' => $history->currentPage(),
                'last_page' => $history->lastPage(),
                'total' => $history->total(),
            ],
        ]);
    }
}
