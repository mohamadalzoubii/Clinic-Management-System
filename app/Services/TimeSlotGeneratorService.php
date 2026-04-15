<?php

namespace App\Services;

use Carbon\Carbon;

class TimeSlotGeneratorService
{
    public function generate(string $startTime, string $endTime, string $slotDuration, array $bookedSlots)
    {
        $avaliableSlots = [];

        $currentSlot = Carbon::parse($startTime);
        $end = Carbon::parse($endTime);

        $duration = (int) $slotDuration;

        while ($currentSlot->copy()->addMinutes($duration)->lte($end)) {

            $timeString = $currentSlot->format('H:i');

            if (!in_array($timeString, $bookedSlots)) {
                $avaliableSlots[] = $timeString;
            }

            $currentSlot->addMinute($duration);
        }
        return $avaliableSlots;
    }
}
