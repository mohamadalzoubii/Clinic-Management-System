<?php

namespace App\Actions\Medical\Doctor;

use App\Services\Medical\DoctorScheduleVersionService;
use App\Services\VacationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class GetDoctorAvailableDaysAction
{
    public function __construct(
        private readonly VacationService $vacationService,
        private readonly DoctorScheduleVersionService $versionService,
    ) {}

    public function execute(int $doctorId, int $daysAhead = 7): array
    {
        $this->vacationService->syncExpiredVacations($doctorId);

        $availableDates = [];
        $startDate = Carbon::today();
        $lookAheadDays = max($daysAhead * 14, 30);
        $searchEndDate = Carbon::today()->addDays($lookAheadDays - 1);
        $versions = $this->versionService->versionsForDoctor($doctorId);

        if ($versions->isEmpty()) {
            return [];
        }

        $period = CarbonPeriod::create($startDate, $searchEndDate);

        foreach ($period as $date) {
            $dayName = strtolower($date->englishDayOfWeek);
            $versionItem = $this->versionService->resolveItemFromCollection($versions, $date);

            if (! $versionItem) {
                continue;
            }

            if (config('app.debug')) {
                $version = $this->versionService->resolveVersionFromCollection($versions, $date);

                Log::debug('Doctor available-days version selected', [
                    'doctor_id' => $doctorId,
                    'date' => $date->format('Y-m-d'),
                    'version_id' => $version?->id,
                    'effective_from_date' => $version?->effective_from_date?->toDateString(),
                ]);
            }

            if (! $this->vacationService->isBlockingDate($doctorId, $date, false)) {
                $availableDates[] = [
                    'full_date' => $date->format('Y-m-d'),
                    'day_name' => $date->format('D'),
                    'day_number' => $date->format('d'),
                    'month_name' => $date->format('M'),
                ];

                if (count($availableDates) >= $daysAhead) {
                    break;
                }
            }
        }

        return $availableDates;
    }
}
