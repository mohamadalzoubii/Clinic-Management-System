<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ConsultationResource;
use App\Models\Patient;

class PatientHistoryController extends Controller
{
    public function index(Patient $patient)
    {

        $history = $patient->consultation()->WithFullDetails($patient)->paginate(10);

        return ConsultationResource::collection($history);
    }
}
