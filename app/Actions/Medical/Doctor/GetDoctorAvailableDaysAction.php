<?php

namespace App\Actions\Medical\Doctor;

use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GetDoctorAvailableDaysAction
{
    public function execute(int $doctorId, int $daysAhead = 7): array
    {
        $workingDays = DoctorSchedule::forDoctorActive($doctorId)
            ->pluck('day_of_week')
            ->map(fn ($day) => strtolower($day->value ?? $day))
            ->toArray();

        if (empty($workingDays)) {
            return [];
        }

        $availableDates = [];

        $period = CarbonPeriod::create(Carbon::today(),
            Carbon::today()->addDays($daysAhead - 1));

        foreach ($period as $date) {
            $dayName = strtolower($date->englishDayOfWeek);

            if (in_array($dayName, $workingDays)) {
                $availableDates[] = [
                    'full_date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('D'),
                    'day_number' => $date->format('d'),
                    'month_name' => $date->format('M'),
                ];
            }
        }

        return $availableDates;
    }
}
