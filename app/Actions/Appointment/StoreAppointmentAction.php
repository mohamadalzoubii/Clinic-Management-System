<?php

namespace App\Actions\Appointment;

use App\DTOs\Appointment\StorAppointmentData;
use App\Enums\Medical\AppointmentStatus;
use App\Events\AppointmentChanged;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Services\AttachmentService;
use App\Services\FinancialService;
use App\Services\Medical\DoctorScheduleVersionService;
use App\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreAppointmentAction
{
    public function __construct(
        private readonly AttachmentService $attachmentService,
        private readonly FinancialService $financialService,
        private readonly VacationService $vacationService,
        private readonly DoctorScheduleVersionService $versionService,
    ) {}

    public function execute(int $patientId, StorAppointmentData $dto)
    {
        return DB::transaction(function () use ($patientId, $dto) {

            $date = Carbon::parse($dto->date);
            $dayName = strtolower($date->englishDayOfWeek);
            $startTime = Carbon::parse($dto->time);

            if ($date->isToday() && $startTime->lt(Carbon::now())) {
                throw new BusinessLogicException('you cant book appointment at this time');
            }

            if ($date->isPast() && ! $date->isToday()) {
                throw new BusinessLogicException('you cant book appointment at this time');
            }

            if ($this->vacationService->isBlockingDate($dto->doctorId, $date)) {
                throw new BusinessLogicException('Doctor is unavailable on the selected date.');
            }

            $versions = $this->versionService->versionsForDoctor($dto->doctorId);
            $schedule = $this->versionService->resolveItemFromCollection($versions, $date);

            if (! $schedule) {
                throw new BusinessLogicException('the selected doctor does not work in this day');
            }

            $isBooked = Appointment::where('doctor_id', $dto->doctorId)
                ->conflicting($dto->date, $startTime)
                ->lockForUpdate()
                ->exists();

            if ($isBooked) {
                throw new BusinessLogicException('Sorry, this time slot has just been booked by another patient.');
            }

            $durction = (int) $schedule->slot_duration;
            $endTime = $startTime->copy()->addMinutes($durction);

            $appointment = Appointment::create([
                'patient_id' => $patientId,
                'doctor_id' => $dto->doctorId,
                'appointment_date' => $dto->date,
                'start_time' => $startTime->format('H:i:s'),
                'end_time' => $endTime->format('H:i:s'),
                'status' => AppointmentStatus::PENDING->value,
                'reason' => $dto->reason,
            ]);

            if (! empty($dto->files)) {
                $this->attachmentService->uploadMany(
                    $dto->files,
                    $appointment,
                    'patient_uploads'
                );
            }

            $invoice = $this->financialService->payForAppointmentAndCreateInvoice($appointment->loadMissing([
                'doctor.user', 'patient.user',
            ]));

            event(new AppointmentChanged(
                doctorId: (int) $appointment->doctor_id,
                appointmentId: (int) $appointment->id,
                changeType: 'booked',
            ));

            return [
                'appointment' => $appointment->load('attachments'),
                'invoice' => $invoice,
                'current_balance' => $appointment->patient->user->fresh()->wallet_balance,
            ];
        });
    }
}
