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
}
