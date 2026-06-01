<?php

namespace App\Http\Controllers\Api\V1\Secretary;

use App\Enums\Medical\AppointmentStatus;
use App\Http\Filters\V1\AppointmentFilter;
use App\Http\Filters\V1\PatientFilter;
use App\Http\Controllers\Controller;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\Api\V1\Admin\PatientResource;
use App\Models\Invoice;
use App\Models\Appointment;
use App\Models\Package;
use App\Models\Patient;
use App\Services\Admin\PatientService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class PatientController extends Controller
{
    use ApiResponses;

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
            ->latest('id')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = collect($invoices->items())->map(function (Invoice $invoice) {
            $patientUser = $invoice->user?->patient?->user;

            return [
                'id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'amount' => $invoice->amount,
                'status' => $invoice->status?->value ?? $invoice->status,
                'entry_type' => $invoice->entry_type,
                'paid_at' => $invoice->paid_at?->toISOString(),
                'download_url' => url("/api/v1/invoices/{$invoice->id}/download"),
                'patient' => [
                    'id' => $invoice->user?->patient?->id,
                    'first_name' => $patientUser?->first_name,
                    'last_name' => $patientUser?->last_name,
                    'email' => $patientUser?->email,
                ],
            ];
        });

        return $this->ok('Invoices retrieved successfully.', [
            'invoices' => $items,
            'meta' => [
                'current_page' => $invoices->currentPage(),
                'last_page' => $invoices->lastPage(),
                'total' => $invoices->total(),
            ],
        ]);
    }

    public function store(Request $request, PatientService $service)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'date_of_birth' => ['nullable', 'date'],
            'emergency_contact_name' => ['nullable', 'string', 'max:255'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:20'],
            'allergies' => ['nullable', 'string'],
            'chronic_diseases' => ['nullable', 'string'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'gender' => ['nullable', 'string', 'max:50'],
            'blood_type' => ['nullable', 'string', 'max:50'],
        ]);

        $patient = $service->store($data);

        return $this->success('Patient account created successfully.', [
            'patient' => new PatientResource($patient->loadMissing('user')),
        ], 201);
    }

    public function buyPackage(Request $request, Patient $patient, PatientService $service)
    {
        $data = $request->validate([
            'package_id' => ['required', 'exists:packages,id'],
        ]);

        $package = Package::findOrFail($data['package_id']);
        $result = $service->buyPackageForPatient($patient->user, $package);

        return $this->ok('Package purchased successfully.', [
            'patient' => new PatientResource($patient->loadMissing('user')),
            'current_balance' => $result['user']->wallet_balance,
            'invoice_id' => $result['invoice']->id,
            'invoice_number' => $result['invoice']->invoice_number,
        ]);
    }
}