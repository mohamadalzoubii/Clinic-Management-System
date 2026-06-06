<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\Patient\StorePatientData;
use App\DTOs\Patient\UpdatePatientData;
use App\Http\Controllers\Controller;
use App\Http\Filters\V1\PatientFilter;
use App\Http\Requests\Admin\StorePatientRequest;
use App\Http\Requests\Admin\UpdatePatientRequest;
use App\Http\Resources\Api\V1\Admin\PatientResource;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Models\Patient;
use App\Services\Admin\PatientService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly PatientService $service) {}

    public function index(PatientFilter $filter)
    {
        $patients = Patient::filter($filter)
            ->with('user')
            ->withCount(['appointments as appointments_count' => function ($query) {
                $query->where('status', '!=', \App\Enums\Medical\AppointmentStatus::CANCELLED->value);
            }])
            ->orderByDesc('appointments_count')
            ->latest()
            ->paginate(10);

        return PatientResource::collection($patients);
    }

    public function show(Patient $patient)
    {
        return new PatientResource($patient->loadMissing('user'));
    }

    public function store(StorePatientRequest $request)
    {
        $patient = $this->service->store(StorePatientData::formRequest($request));

        return $this->success('Patient created successfully.', [
            'patient' => new PatientResource($patient->loadMissing('user')),
        ], 201);
    }

    public function update(UpdatePatientRequest $request, Patient $patient)
    {
        $patient = $this->service->update($patient, UpdatePatientData::formRequest($request));

        return $this->ok('Patient updated successfully.', [
            'patient' => new PatientResource($patient),
        ]);
    }

    public function destroy(Patient $patient)
    {
        $this->service->delete($patient);

        return $this->ok('Patient deleted successfully.');
    }

    public function invoices(Request $request, Patient $patient)
    {
        $page = max(1, $request->integer('page', 1));
        $perPage = max(1, $request->integer('limit', 8));

        $invoices = $patient->user
            ->invoices()
            ->with('user.patient.user')
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->ok('Invoices retrieved successfully.', [
            'invoices' => InvoiceResource::collection($invoices->getCollection())->resolve(),
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }
}
