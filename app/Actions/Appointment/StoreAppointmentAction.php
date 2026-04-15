<?php

namespace App\Actions\Appointment;

use App\DTOs\Appointment\StorAppointmentData;
use App\Enums\Medical\AppointmentStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Models\DoctorSchedule;
use App\Services\AttachmentService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StoreAppointmentAction
{
    public function __construct(private readonly AttachmentService $attachmentService) {}

    public function execute(int $patientId, StorAppointmentData $dto)
    {

        return DB::transaction(function () use ($patientId, $dto) {

            $date = Carbon::parse($dto->date);
            $dayName = strtolower($date->englishDayOfWeek);
            $startTime = Carbon::parse($dto->time);

            $schedule = DoctorSchedule::where('doctor_id', $dto->doctorId)
                ->activeOnDay($dayName)
                ->first();

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

            return $appointment->load('attachments');
        });
    }
}
