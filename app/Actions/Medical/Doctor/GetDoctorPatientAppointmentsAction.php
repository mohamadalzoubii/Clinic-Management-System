<?php

namespace App\Actions\Medical\Doctor;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Collection;

class GetDoctorPatientAppointmentsAction
{
    public function execute(
        ?int $doctorId,
        Patient $patient,
        ?string $date = null,
        ?string $search = null,
        ?string $specialization = null,
    ): Collection {
        return Appointment::query()
            ->when($doctorId, fn ($q) => $q->where('doctor_id', $doctorId))
            ->where('patient_id', $patient->id)
            ->completed()
            ->when($date, fn ($query) => $query->whereDate('appointment_date', $date))
            ->when($search, function ($query) use ($search) {
                $query->whereHas('doctor.user', function ($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->when($specialization, function ($query) use ($specialization) {
                $specializations = $this->normalizeSpecialization($specialization);

                $query->whereHas('doctor', function ($doctorQuery) use ($specializations) {
                    $doctorQuery->where(function ($specializationQuery) use ($specializations) {
                        foreach ($specializations as $spec) {
                            $formattedSpecialization = str_replace('*', '%', trim($spec));
                            $specializationQuery->orWhere('specialization', 'like', $formattedSpecialization);
                        }
                    });
                });
            })
            ->with(['patient.user', 'consultation.prescriptionItems', 'doctor.user', 'doctor'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get();
    }

    private function normalizeSpecialization(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }
}
