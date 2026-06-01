<?php

namespace App\Actions\Appointment;

use App\Enums\Medical\AppointmentStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use Illuminate\Support\Facades\DB;

class MarkAppointmentNoShowAction
{
    public function execute(Appointment $appointment, int $doctorId)
    {
        return DB::transaction(function () use ($appointment, $doctorId) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->where('doctor_id', $doctorId)
                ->lockForUpdate()
                ->with(['doctor.user', 'patient.user'])
                ->firstOrFail();

            if (! $appointment->isPending()) {
                throw new BusinessLogicException('Only pending appointments can be marked as no show.');
            }

            $appointment->update([
                'status' => AppointmentStatus::NO_SHOW->value,
            ]);

            return $appointment->fresh(['doctor.user', 'patient.user']);
        });
    }
}