<?php

namespace App\Http\Controllers\Api\V1\Secretary;

use App\Enums\Medical\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Filters\V1\AppointmentFilter;
use App\Http\Filters\V1\PatientFilter;
use App\Http\Requests\Admin\StorePatientRequest;
use App\Http\Resources\Api\V1\Admin\PatientResource;
use App\Http\Resources\Api\V1\InvoiceResource;
use App\Http\Resources\AppointmentResource;
use App\DTOs\Patient\StorePatientData;
use App\Models\Appointment;
use App\Models\Package;
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
                $query->where('status', '!=', AppointmentStatus::CANCELLED->value);
            }])
            ->orderByDesc('appointments_count')
            ->latest()
            ->paginate(10);

        return PatientResource::collection($patients);
    }

    public function show(Patient $patient)
    {
        return new PatientResource($patient->loadMissing('user')->loadCount(['appointments as appointments_count' => function ($query) {
            $query->where('status', '!=', AppointmentStatus::CANCELLED->value);
        }]));
    }

    public function appointments(Patient $patient, AppointmentFilter $filter)
    {
        $appointments = Appointment::query()
            ->where('patient_id', $patient->id)
            ->with(['doctor.user', 'patient.user', 'invoices', 'attachments'])
            ->filter($filter)
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate(8);

        return AppointmentResource::collection($appointments);
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

    public function store(StorePatientRequest $request)
    {
        $patient = $this->service->store(StorePatientData::formRequest($request));

        return $this->success('Patient account created successfully.', [
            'patient' => new PatientResource($patient->loadMissing('user')),
        ], 201);
    }

    public function buyPackage(Request $request, Patient $patient)
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
        ]);

        $package = Package::findOrFail($data['package_id']);
        $result = $this->service->buyPackageForPatient($patient->user, $package);

        return $this->ok('Package purchased successfully.', [
            'patient' => new PatientResource($patient->loadMissing('user')),
            'current_balance' => $result['user']->wallet_balance,
            'invoice_id' => $result['invoice']->id,
            'invoice_number' => $result['invoice']->invoice_number,
        ]);
    }
}
