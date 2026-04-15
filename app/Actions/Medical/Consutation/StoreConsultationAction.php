<?php

namespace App\Actions\Medical\Consutation;

use App\DTOs\Consutation\StoreConsultationDTO;
use App\Enums\Medical\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use Illuminate\Support\Facades\DB;

class StoreConsultationAction
{
    public function execute(Appointment $appointment, StoreConsultationDTO $dto)
    {
        return DB::transaction(function () use ($appointment, $dto) {
            $appointment->update(['status' => AppointmentStatus::COMPLETED->value]);

            $consultation = Consultation::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'notes' => $dto->notes,
                'next_visit_date' => $dto->nextVisitDate,
            ]);

            if ($dto->medicines->isNotEmpty()) {
                $consultation->prescriptionItems()->createMany(
                    $dto->medicines->map(fn ($m) => [
                        'medicine_name' => $m->name, 'dosage' => $m->dosage,
                        'frequency' => $m->frequency, 'duration' => $m->duration, 'notes' => $m->itemNotes,
                    ])->toArray()
                );
            }

            return $consultation->load('prescriptionItems');
        });
    }
}
