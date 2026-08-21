<?php

namespace App\Actions\Appointment;

use App\Enums\Medical\AppointmentStatus;
use App\Events\AppointmentChanged;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Services\FinancialService;
use Illuminate\Support\Facades\DB;

class CancelAppointmentAction
{
    public function __construct(private readonly FinancialService $financialService) {}

    public function execute(Appointment $appointment, int $patinetId)
    {
        return $this->executeForPatient($appointment, $patinetId);
    }

    public function executeForPatient(Appointment $appointment, int $patinetId)
    {
        return DB::transaction(function () use ($appointment, $patinetId) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['doctor.user', 'patient.user'])
                ->firstOrFail();

            if (! $appointment->isPending()) {
                throw new BusinessLogicException('Only pending appointments can be canceled.');
            }

            if (! $appointment->canBeCancelled()) {
                throw new BusinessLogicException('Cannot cancel less than 2 hours before the appointment.');
            }

            $appointment->status = AppointmentStatus::CANCELLED;
            $appointment->save();

            $settlement = $this->financialService->refundForPatientCancellation($appointment);

            event(new AppointmentChanged(
                doctorId: (int) $appointment->doctor_id,
                appointmentId: (int) $appointment->id,
                changeType: 'cancelled',
            ));

            return [
                'appointment' => $appointment->fresh(['doctor.user', 'patient.user', 'invoices']),
                'refund_invoice' => $settlement['refund_invoice'],
                'current_balance' => $appointment->patient->user->fresh()->wallet_balance,
            ];
        });

    }

    public function executeForDoctor(Appointment $appointment, int $doctorId)
    {
        return DB::transaction(function () use ($appointment, $doctorId) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->with(['doctor.user', 'patient.user'])
                ->firstOrFail();

            if (! $appointment->isPending()) {
                throw new BusinessLogicException('Only pending appointments can be canceled.');
            }

            $appointment->status = AppointmentStatus::CANCELLED;
            $appointment->save();

            $settlement = $this->financialService->refundForDoctorCancellation($appointment);

            event(new AppointmentChanged(
                doctorId: (int) $appointment->doctor_id,
                appointmentId: (int) $appointment->id,
                changeType: 'cancelled',
            ));

            return [
                'appointment' => $appointment->fresh(['doctor.user', 'patient.user', 'invoices']),
                'refund_invoice' => $settlement['refund_invoice'],
                'current_balance' => $appointment->patient->user->fresh()->wallet_balance,
            ];

        });
    }
}
