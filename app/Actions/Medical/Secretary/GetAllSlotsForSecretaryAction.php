<?php

namespace App\Actions\Medical\Secretary;

use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Services\Medical\DoctorScheduleVersionService;
use App\Services\TimeSlotGeneratorService;
use App\Services\VacationService;
use Carbon\Carbon;

class GetAllSlotsForSecretaryAction
{
    public function __construct(
        private readonly TimeSlotGeneratorService $slotGenerator,
        private readonly VacationService $vacationService,
        private readonly DoctorScheduleVersionService $versionService,
    ) {}

    public function execute(string $doctorId, string $dateStarting): array
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

        // كل السلوتات الممكنة نظرياً (بدون طرح المحجوز)
        $allSlots = $this->slotGenerator->generate(
            $schedule->start_time,
            $schedule->end_time,
            (int) $schedule->slot_duration,
            [] // ما منمرر بوكد سلوتس هون، بدنا كلهن
        );

        // السلوتات المحجوزة فعلياً (نفس منطق blockingTimeOnDate)
        $bookedTimes = Appointment::where('doctor_id', $doctorId)
            ->blockingTimeOnDate($date)
            ->pluck('start_time')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

        $blockedTimes = BlockedSlot::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->pluck('start_time')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

        $result = array_map(function ($slot) use ($bookedTimes, $blockedTimes) {
            $slotTime = is_array($slot) ? ($slot['start_time'] ?? $slot['time'] ?? $slot) : $slot;

            $status = 'available';
            if (in_array($slotTime, $bookedTimes)) {
                $status = 'booked';
            } elseif (in_array($slotTime, $blockedTimes)) {
                $status = 'blocked';
            }

            return [
                'time' => $slotTime,
                'status' => $status,
            ];
        }, $allSlots);

        if ($date->isToday()) {
            $slotDuration = (int) $schedule->slot_duration;
            $now = Carbon::now();

            $result = array_values(array_filter($result, function ($slot) use ($slotDuration, $now) {
                $slotEnd = Carbon::parse($slot['time'])->addMinutes($slotDuration);

                return $slotEnd->gt($now);
            }));
        }

        return $result;
    }
}
