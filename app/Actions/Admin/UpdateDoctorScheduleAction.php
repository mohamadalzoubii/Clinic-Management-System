<?php

namespace App\Actions\Admin;

use App\Actions\Medical\Doctor\GetDoctorAgendaAction;
use App\DTOs\Admin\ScheduleData;
use App\Models\Doctor;
use App\Services\Medical\DoctorScheduleVersionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class UpdateDoctorScheduleAction
{
    public function __construct(
        protected GetDoctorAgendaAction $agendaAction,
        protected DoctorScheduleVersionService $versionService
    ) {}

    public function execute(Doctor $doctor, ScheduleData $data, ?int $adminId): array
    {
        $agenda = $this->agendaAction->execute($doctor->id, 7);
        if (! empty($agenda)) {
            $lastDate = end($agenda)['full_date'] ?? null;
            $effectiveFromDate = $lastDate ? Carbon::parse($lastDate)->addDay()->toDateString() : Carbon::tomorrow()->toDateString();
        } else {
            $effectiveFromDate = Carbon::tomorrow()->toDateString();
        }

        $version = $this->versionService->createVersionFromSchedules(
            $doctor,
            $data->schedules,
            $data->slotDuration,
            $effectiveFromDate,
            $adminId
        );

        $selectedDays = collect($data->schedules)
            ->pluck('day_of_week')
            ->map(fn ($day) => strtolower($day))
            ->unique()
            ->values()
            ->all();

        Log::info('Doctor working schedule updated via Action.', [
            'doctor_id' => $doctor->id,
            'version_id' => $version->id,
            'effective_from_date' => $effectiveFromDate,
            'slot_duration' => $data->slotDuration,
            'selected_days' => $selectedDays,
        ]);

        return [
            'version' => $version,
            'effective_from_date' => $effectiveFromDate,
        ];
    }
}
