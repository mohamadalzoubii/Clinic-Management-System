<?php

namespace App\Actions\Medical\Consutation;

use App\DTOs\Consultation\StoreConsultationDTO;
use App\Enums\Medical\AppointmentStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Services\FinancialService;
use Illuminate\Support\Facades\DB;

class StoreConsultationAction
{
    public function __construct(private readonly FinancialService $financialService) {}

    public function execute(Appointment $appointment, StoreConsultationDTO $dto)
    {
        return DB::transaction(function () use ($appointment, $dto) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['doctor.user', 'patient.user'])
                ->firstOrFail();

            if (! $appointment->isPending()) {
                throw new BusinessLogicException('Only pending appointments can be completed.');
            }

            $appointment->update(['status' => AppointmentStatus::COMPLETED->value]);

            $consultation = Consultation::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'anamnesis' => $dto->anamnesis,
                'symptoms' => $dto->symptoms,
                'diagnosis' => $dto->diagnosis,
                'next_visit_date' => $dto->nextVisitDate,
            ]);

            if ($dto->medicines->isNotEmpty()) {
                $medicinesData = $dto->medicines->map(function ($medicineDTO) {
                    return [
                        'medicine_name' => $medicineDTO->name,
                        'category' => $medicineDTO->category,
                        'dosage' => $medicineDTO->dosage,
                        'form_and_quantity' => $medicineDTO->formAndQuantity,
                        'frequency' => $medicineDTO->frequency,
                        'duration' => $medicineDTO->duration,
                        'special_instructions' => $medicineDTO->specialInstructions,
                        'storage_instructions' => $medicineDTO->storageInstructions,
                        'side_effects' => $medicineDTO->sideEffects,
                        'allergy_warnings' => $medicineDTO->allergyWarnings,
                    ];
                })->toArray();

                $consultation->prescriptionItems()->createMany($medicinesData);
            }

            $payoutInvoices = $this->financialService->payoutForCompletion70_30($appointment);

            return [
                'consultation' => $consultation->load('prescriptionItems'),
                'payout_invoices' => $payoutInvoices,
                'current_balance' => $appointment->doctor->user->fresh()->wallet_balance,
            ];
        });
    }
}
