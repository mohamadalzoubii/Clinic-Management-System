<?php

namespace App\Actions\Appointment;

use App\DTOs\Appointment\RescheduleAppointmentData;
use App\Exceptions\BusinessLogicException;
use App\Events\AppointmentChanged;
use App\Models\Appointment;
use App\Services\Medical\DoctorScheduleVersionService;
use App\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RescheduleAppointmentAction
{
    public function __construct(
        private readonly VacationService $vacationService,
        private readonly DoctorScheduleVersionService $versionService,
    ) {}

    public function execute(Appointment $appointment, int $patientId, RescheduleAppointmentData $dto): Appointment
    {
        return DB::transaction(function () use ($appointment, $patientId, $dto) {
            $appointment = Appointment::query()
                ->whereKey($appointment->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($appointment->patient_id !== $patientId) {
                throw new BusinessLogicException('Unauthorized to reschedule this appointment.');
            }

            if (! $appointment->isPending()) {
                throw new BusinessLogicException('Only pending appointments can be rescheduled.');
            }

            if (! $appointment->canBeCancelled()) {
                throw new BusinessLogicException('Cannot reschedule less than 2 hours before the appointment.');
            }

            if (($appointment->rescheduled ?? false) === true) {
                throw new BusinessLogicException('This appointment has already been rescheduled once.');
            }



            $date = Carbon::parse($dto->date);
            $startTime = Carbon::parse($dto->time);

            $doctorId = $appointment->doctor_id;

            if ($this->vacationService->isBlockingDate($doctorId, $date)) {
                throw new BusinessLogicException('Doctor is unavailable on the selected date.');
            }

            $versions = $this->versionService->versionsForDoctor($doctorId);
            $schedule = $this->versionService->resolveItemFromCollection($versions, $date);

            if (! $schedule) {
                throw new BusinessLogicException('The doctor does not work on this day.');
            }

            $isBooked = Appointment::query()
                ->where('doctor_id', $doctorId)
                ->where('id', '!=', $appointment->id)
                ->conflicting($dto->date, $startTime)
                ->lockForUpdate()
                ->exists();

            if ($isBooked) {
                throw new BusinessLogicException('Sorry, this time slot has just been booked by another patient.');
            }

            $duration = (int) $schedule->slot_duration;
            $endTime = $startTime->copy()->addMinutes($duration);

            $appointment->update([
                'appointment_date' => $dto->date,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'reason' => $dto->reason ?? $appointment->reason,
                'rescheduled' => true,
            ]);

            event(new AppointmentChanged(
                doctorId: (int) $appointment->doctor_id,
                appointmentId: (int) $appointment->id,
                changeType: 'rescheduled',
            ));

            return $appointment->fresh(['doctor.user', 'patient.user']);
        });
    }
}

