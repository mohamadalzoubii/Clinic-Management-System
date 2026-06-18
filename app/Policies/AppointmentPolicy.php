<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function addConsultation(User $user, Appointment $appointment): bool
    {

        return $user->doctor && $user->doctor->id === $appointment->doctor_id;
    }

    public function cansel(Uesr $user, Appointment $appointment)
    {
        return $user->patient && $user->patient->id === $appointment->patient_id;
    }
}
