<?php

namespace App\Actions\Medical\Doctor;

use App\Models\Appointment;
use Illuminate\Support\Collection;

class GetDoctorSummaryAction
{
    public function execute(?int $doctorId = null, string $search = ''): Collection
    {
        $appointments = Appointment::query()
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->completed()
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('patient.user', function ($patientQuery) use ($search) {
                    $patientQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->with(['patient.user', 'consultation.prescriptionItems'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get();

        return $appointments
            ->groupBy('patient_id')
            ->map(function ($patientAppointments) {
                $latestAppointment = $patientAppointments
                    ->sortByDesc(fn ($a) => [$a->appointment_date->format('Y-m-d'), $a->start_time])
                    ->first();

                return [
                    'patient' => $latestAppointment?->patient,
                    'completed_appointments_count' => $patientAppointments->count(),
                    'last_completed_at' => $latestAppointment?->created_at,
                ];
            })
            ->values();
    }
}
