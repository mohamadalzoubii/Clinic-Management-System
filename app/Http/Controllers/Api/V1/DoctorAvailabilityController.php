<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Medical\Doctor\GetDoctorAgendaAction;
use App\Actions\Medical\Doctor\GetDoctorAvailableDaysAction;
use App\Actions\Medical\Doctor\GetDoctorAvailableSlotsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Doctor\GetAvailableSlotsRequest;
use App\Models\Doctor;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class DoctorAvailabilityController extends Controller
{
    use ApiResponses;

    public function getAvailableDays(Doctor $doctor, GetDoctorAvailableDaysAction $action)
    {

        $availableDays = $action->execute($doctor->id);

        $message = empty($availableDays)
            ? 'Sorry, this doctor currently has no available days.'
            : 'Available days retrieved successfully.';

        return $this->ok($message, [
            'type' => 'available_days',
            'attributes' => [
                'doctor_id' => $doctor->id,
                'days' => $availableDays,
            ],
        ]);
    }

    public function getAvailableSlots(
        GetAvailableSlotsRequest $request,
        Doctor $doctor,
        GetDoctorAvailableSlotsAction $action
    ) {

        $date = $request->validated('date');

        $availableSlots = $action->execute($doctor->id, $date);

        $message = empty($availableSlots)
            ? 'Sorry, there are no available slots for this doctor on the selected date.'
            : 'Available slots retrieved successfully.';

        return $this->ok($message, [
            'type' => 'available_slots',
            'attributes' => [
                'doctor_id' => $doctor->id,
                'date' => $date,
                'slots' => $availableSlots,
            ],
        ]);
    }

    public function getAgenda(Request $request, GetDoctorAgendaAction $action)
    {
        $doctorId = $request->user()->doctor->id;

        $agenda = $action->execute($doctorId);

        $message = empty($agenda)
            ? 'You have no scheduled days.'
            : 'Your agenda retrieved successfully.';

        return $this->ok($message, [
            'type' => 'doctor_agenda',
            'attributes' => [
                'doctor_id' => $doctorId,
                'agenda' => $agenda,
            ],
        ]);
    }
}
