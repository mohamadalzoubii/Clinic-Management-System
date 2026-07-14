<?php

namespace App\Http\Controllers;

use App\Actions\Appointment\MarkAppointmentNoShowAction;
use App\Actions\Appointment\CancelAppointmentAction;
use App\Actions\Appointment\RescheduleAppointmentAction;
use App\Actions\Appointment\StoreAppointmentAction;
use App\Actions\Medical\Doctor\GetDoctorAvailableSlotsAction;
use App\DTOs\Appointment\RescheduleAppointmentData;
use App\DTOs\Appointment\StorAppointmentData;

use App\Http\Requests\Appointment\RescheduleAppointmentRequest;
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

        return new AppointmentResource($appointment->loadMissing('doctor.user', 'patient.user', 'consultation.prescriptionItems'));
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
        $patientId = $request->user()->patient->id;

        $dto = StorAppointmentData::formRequest($request);

        $result = $action->execute($patientId, $dto);

        return $this->ok(
            'Appointment booked successfully',
            [
                'appointment' => new AppointmentResource($result['appointment']),
                'invoice_id' => $result['invoice']->id,
                'invoice_number' => $result['invoice']->invoice_number,
                'current_balance' => $result['current_balance'],
            ]
        );
    }

    public function cansal(Appointment $appointment, CancelAppointmentAction $action)
    {
        $patientId = auth()->user()->patient->id;

        $result = $action->executeForPatient($appointment, $patientId);

        return $this->ok(
            'Appointment canceled successfully',
            [
                'appointment' => new AppointmentResource($result['appointment']),
                'refund_invoice_id' => $result['refund_invoice']->id,
                'refund_invoice_number' => $result['refund_invoice']->invoice_number,
                'current_balance' => $result['current_balance'],
            ]
        );
    }

    public function markNoShow(Appointment $appointment, MarkAppointmentNoShowAction $action)
    {
        $doctorId = auth()->user()->doctor->id;

        $updatedAppointment = $action->execute($appointment, $doctorId);

        return $this->ok('Appointment marked as no show successfully.', [
            'appointment' => new AppointmentResource($updatedAppointment),
        ]);
    }

    public function cancelAsDoctor(Appointment $appointment, CancelAppointmentAction $action)
    {
        $doctorId = auth()->user()->doctor->id;

        $result = $action->executeForDoctor($appointment, $doctorId);

        return $this->ok('Appointment canceled successfully.', [
            'appointment' => new AppointmentResource($result['appointment']),
            'refund_invoice_id' => $result['refund_invoice']->id,
            'refund_invoice_number' => $result['refund_invoice']->invoice_number,
            'current_balance' => $result['current_balance'],
        ]);
    }

    public function reschedule(
        \App\Http\Requests\Appointment\RescheduleAppointmentRequest $request,
        Appointment $appointment,
        RescheduleAppointmentAction $action,
    ) {
        $patientId = $request->user()->patient->id;

        $dto = RescheduleAppointmentData::formRequest($request);

        $updatedAppointment = $action->execute($appointment, $patientId, $dto);

        return $this->ok(
            'Appointment rescheduled successfully',
            [
                'appointment' => new AppointmentResource($updatedAppointment),
            ]
        );
    }
}

