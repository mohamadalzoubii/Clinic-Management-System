<?php

namespace App\Http\Controllers;

use App\Actions\Medical\Patient\PatientProfileAction;
use App\DTOs\Patient\PatientProfileData;
use App\DTOs\Patient\UpdatePatientData;
use App\Http\Filters\V1\AppointmentFilter;
use App\Http\Requests\Admin\UpdatePatientRequest;
use App\Http\Requests\Api\V1\Patient\PatientProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Appointment;
use App\Models\Patient;
use App\Traits\ApiResponses;

class PatientController extends Controller
{
    use ApiResponses;

    public function index()
    {
        return PatientResource::collection(Patient::with('user')->get());
    }

    public function show(Patient $patient)
    {
        return new PatientResource($patient->load('user'));
    }

    public function appointments(AppointmentFilter $filter)
    {
        $patient = request()->user()->patient;

        $appointments = Appointment::query()
            ->where('patient_id', $patient->id)
            ->with(['doctor.user', 'patient.user', 'invoices', 'attachments'])
            ->filter($filter)
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->paginate(8);

        return AppointmentResource::collection($appointments);
    }


    public function completeProfile(PatientProfileRequest $request, PatientProfileAction $action)
    {
        $dto = PatientProfileData::fromRequest($request);

        $user = $action->execute($request->user(), $dto);

        $user->load('patient');

        return $this->ok('Your profile is completed successfully', [
            'user' => new UserResource($user),
        ]);
    }


    public function updateProfile(PatientProfileRequest $request, PatientProfileAction $action)
    {
        $dto = PatientProfileData::fromRequest($request);


        $user = $action->execute($request->user(), $dto);

        $user->load('patient');

        return $this->ok('Your profile is updated successfully', [
            'user' => new UserResource($user),
        ]);
    }

    public function visitSummary()
    {
        $patient = request()->user()->patient;

        $consultations = \App\Models\Consultation::where('patient_id', $patient->id)
            ->with(['doctor.user', 'appointment'])
            ->orderByDesc('created_at')
            ->get();

            
        $visits = $consultations->map(function ($c) {
            $appt = $c->appointment;
            $doc = $c->doctor;
            return [
                'id' => $c->id,
                'visit_date' => $appt?->appointment_date,
                'doctor' => [
                    'id' => $doc->id,
                    'name' => $doc->user->first_name . ' ' . $doc->user->last_name,
                    'specialization' => $doc->specialization,
                    'photo_url' => $doc->doctor_photo_url,
                ],
                'consultation' => [
                    'anamnesis' => $c->anamnesis,
                    'symptoms' => $c->symptoms,
                    'diagnosis' => $c->diagnosis,
                    'next_visit_date' => $c->next_visit_date?->format('Y-m-d'),
                ],
            ];
        });

        return $this->ok('Visit summary retrieved', ['visits' => $visits]);
    }



    public function walletBalance()
    {
        $patient = request()->user()->patient;

        $walletBalance = $patient->user?->wallet_balance;

        return $this->ok('Wallet balance retrieved', [
            'wallet_balance' => $walletBalance,
        ]);
    }

    public function prescriptions()
    {
        $patient = request()->user()->patient;

        $prescriptions = \App\Models\PrescriptionItem::whereHas('consultation', function ($q) use ($patient) {
            $q->where('patient_id', $patient->id);
        })
            ->with(['consultation.doctor.user', 'consultation.appointment'])
            ->orderByDesc('created_at')
            ->get();

        $list = $prescriptions->map(function ($p) {
            $doc = $p->consultation->doctor;
            $appt = $p->consultation->appointment;

            return [
            'id' => $p->id,
            'medicine_name' => $p->medicine_name,
            'category' => $p->category,
            'dosage' => $p->dosage,
            'form_and_quantity' => $p->form_and_quantity,
            'frequency' => $p->frequency,
            'duration' => $p->duration,
            'special_instructions' => $p->special_instructions,
            'storage_instructions' => $p->storage_instructions,
            'side_effects' => $p->side_effects,
            'allergy_warnings' => $p->allergy_warnings,
            'doctor' => [
                'id' => $doc->id,
                'name' => $doc->user->first_name . ' ' . $doc->user->last_name,
            ],
            'prescribed_on' => $appt?->appointment_date,
        ];
    });

    return $this->ok('Prescriptions retrieved', ['prescriptions' => $list]);
}

}
