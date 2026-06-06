<?php

namespace App\Actions\Medical\Doctor;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;

class GetDoctorPatientAppointmentsAction
{
    public function execute(int $doctorId, Patient $patient, ?string $date = null): Collection
    {
        return Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->completed()
            ->when($date, fn ($query) => $query->whereDate('appointment_date', $date))
            ->with(['patient.user', 'consultation.prescriptionItems'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get();
    }
}
