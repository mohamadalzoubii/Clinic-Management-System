<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\CancelAppointmentAction;
use App\Actions\Appointment\StoreAppointmentAction;
use App\Actions\Medical\Doctor\GetDoctorAvailableSlotsAction;
use App\DTOs\Appointment\StorAppointmentData;
use App\Http\Filters\V1\AppointmentFilter;
use App\Http\Requests\Appointment\StorAppointmentRequest;
use App\Http\Requests\Doctor\GetAvailableSlotsRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use App\Traits\ApiResponses;
use App\Traits\Filterable;

class AppointmentController extends Controller
{
    use ApiResponses, Filterable;

    public function index(AppointmentFilter $filter)
    {

        return AppointmentResource::collection(Appointment::filter($filter)->get());
    }

    public function show(Appointment $appointment)
    {

        return new AppointmentResource($appointment->loadMissing('doctor', 'patient'));
    }

    public function getAvailableSlots(GetAvailableSlotsRequest $request, GetDoctorAvailableSlotsAction $action)
    {
        $doctorId = (int) $request->validated('doctor_id');
        $date = $request->validated('date');

        $availableSlots = $action->execute($doctorId, $date);

        $message = empty($availableSlots)
            ? 'Sorry, there are no available slots for this doctor on the selected date.'
            : 'Available slots retrieved successfully.';

        return $this->ok($message, [
            'type' => 'available_slots',
            'attributes' => [
                'doctor_id' => $doctorId,
                'date' => $date,
                'slots' => $availableSlots,
            ],
        ]);
    }

    public function store(StorAppointmentRequest $request, StoreAppointmentAction $action)
    {
        //        dd($request->allFiles());

        $patientId = $request->user()->patient->id;

        $dto = StorAppointmentData::formRequset($request);

        $appointment = $action->execute($patientId, $dto);

        return $this->ok(
            'Appointment booked successfully',
            [
                new AppointmentResource($appointment),
            ]
        );
    }

    public function cansal(Appointment $appointment, CancelAppointmentAction $action)
    {
        $patientId = auth()->user()->patient->id;

        $updatedAppointment = $action->execute($appointment, $patientId);

        return $this->ok(
            'Appointment canceled successfully',
            [
                new AppointmentResource($updatedAppointment),
            ]
        );
    }
}
