<?php

namespace App\Actions\Medical\Doctor;

use App\Models\Appointment;
use App\Services\Medical\DoctorScheduleVersionService;
use App\Services\TimeSlotGeneratorService;
use App\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GetDoctorAvailableSlotsAction
{
    public function __construct(
        private readonly TimeSlotGeneratorService $slotGenerator,
        private readonly VacationService $vacationService,
        private readonly DoctorScheduleVersionService $versionService,
    ) {}

    public function execute(string $doctorId, string $dateStarting)
    {
        $date = Carbon::parse($dateStarting);

        $this->vacationService->syncExpiredVacations((int) $doctorId);

        if ($this->vacationService->isBlockingDate((int) $doctorId, $date)) {
            return [];
        }

        $versions = $this->versionService->versionsForDoctor((int) $doctorId);
        $schedule = $this->versionService->resolveItemFromCollection($versions, $date);

        if (! $schedule) {
            return [];
        }

        if (config('app.debug')) {
            $version = $this->versionService->resolveVersionFromCollection($versions, $date);

            Log::debug('Doctor available-slots version selected', [
                'doctor_id' => $doctorId,
                'date' => $date->format('Y-m-d'),
                'version_id' => $version?->id,
                'effective_from_date' => $version?->effective_from_date?->toDateString(),
            ]);
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
