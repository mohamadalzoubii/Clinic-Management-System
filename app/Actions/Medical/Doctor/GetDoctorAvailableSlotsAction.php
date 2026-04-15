<?php

namespace App\Actions\Medical\Doctor;

use App\Models\Appointment;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\TimeSlotGeneratorService;
use Carbon\Carbon;

class GetDoctorAvailableSlotsAction
{
    public function __construct(private readonly TimeSlotGeneratorService $slotGenerator) {}

    public function execute(string $doctorId, string $dateStarting)
    {
        $date = Carbon::parse($dateStarting);
        $dayOfWeekName = strtolower($date->englishDayOfWeek);

        $schedule = DoctorSchedule::where('doctor_id', $doctorId)
            ->ActiveOnDay($dayOfWeekName)
            ->first();

            if(!$schedule) {
                return [];
            }

            $bookedSlots = Appointment::where('doctor_id', $doctorId)
            ->blockingTimeOnDate($date) 
            ->pluck('start_time')
            ->map(fn($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

            return $this->slotGenerator->generate(
            $schedule->start_time,
            $schedule->end_time,
            (int) $schedule->slot_duration,
            $bookedSlots
        );
    }
}
