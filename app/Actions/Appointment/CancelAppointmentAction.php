<?php

namespace App\Actions\Appointment;

use App\Enums\Medical\AppointmentStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;

class CancelAppointmentAction
{
    public function execute(Appointment $appointment, int $patinetId)
    {

        if (! $appointment->isPending()) {
            throw new BusinessLogicException('Only pending appointments can be canceled.');
        }

        if (! $appointment->canBeCancelled()) {
            throw new BusinessLogicException('Cannot cancel less than 2 hours before the appointment.');
        }

        $appointment->status = AppointmentStatus::CANCELLED;
        $appointment->save();

        return $appointment;

    }
}
