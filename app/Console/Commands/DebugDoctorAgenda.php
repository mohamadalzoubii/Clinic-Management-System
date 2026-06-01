<?php

namespace App\Console\Commands;

use App\Services\Medical\DoctorScheduleVersionService;
use App\Services\VacationService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Console\Command;

class DebugDoctorAgenda extends Command
{
    protected $signature = 'debug:doctor-agenda {doctorId} {startDate?} {days=14}';

    protected $description = 'Debug doctor agenda: prints per-date version/item/vacation info';

    public function handle(DoctorScheduleVersionService $versionService, VacationService $vacationService): int
    {
        $doctorId = (int) $this->argument('doctorId');
        $startDate = $this->argument('startDate') ? Carbon::parse($this->argument('startDate')) : Carbon::today();
        $days = (int) $this->argument('days');

        $endDate = (clone $startDate)->addDays(max($days - 1, 0));
        $versions = $versionService->versionsForDoctor($doctorId);

        $period = CarbonPeriod::create($startDate, $endDate);
        $out = [];

        foreach ($period as $date) {
            $version = $versionService->resolveVersionFromCollection($versions, $date);
            $item = $version ? $versionService->resolveItemFromCollection($versions, $date) : null;
            $vacBlocking = $vacationService->isBlockingDate($doctorId, $date, false);

            $out[] = [
                'date' => $date->toDateString(),
                'version_id' => $version?->id ?? null,
                'version_effective' => $version?->effective_from_date?->toDateString() ?? null,
                'item_day' => $item?->day_of_week ?? null,
                'vacation_blocking' => $vacBlocking,
            ];
        }

        $this->line(json_encode($out, JSON_PRETTY_PRINT));

        return self::SUCCESS;
    }
}
