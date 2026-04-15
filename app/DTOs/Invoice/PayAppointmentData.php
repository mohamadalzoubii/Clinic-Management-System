<?php

namespace App\DTOs\Invoice;

use App\Models\Appointment;
use App\Models\User;

class PayAppointmentData
{
    public function __construct(
        public readonly Appointment $appointment,
        public readonly User $patientUser,
        public readonly float $price
    ) {}

    public static function fromAppointment(Appointment $appointment): self
    {
        return new self(
            appointment: $appointment,
            patientUser: $appointment->patient->user,
            price: $appointment->doctor->session_price
        );
    }
}
