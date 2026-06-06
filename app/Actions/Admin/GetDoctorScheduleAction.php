<?php

namespace App\Actions\Admin;

use App\Models\Doctor;
use App\Services\Medical\DoctorScheduleVersionService;

class GetDoctorScheduleAction
{
    public function __construct(
        protected DoctorScheduleVersionService $versionService
    ) {}

    public function execute(Doctor $doctor): array
    {
        $doctor->loadMissing('user');

        $currentVersion = $this->versionService->resolveCurrentVersion($doctor->id);
        $currentSchedules = $currentVersion ? $this->versionService->formatItems($currentVersion) : [];

        $draftVersion = $this->versionService->resolveLatestVersionForEditor($doctor->id);
        $draftSchedules = $draftVersion ? $this->versionService->formatItems($draftVersion) : [];

        $slotDuration = $draftSchedules[0]['slot_duration'] ?? $currentSchedules[0]['slot_duration'] ?? 30;

        return [
            'type' => 'doctor_schedule_editor',
            'attributes' => [
                'doctor' => [
                    'id' => $doctor->id,
                    'first_name' => $doctor->user?->first_name,
                    'last_name' => $doctor->user?->last_name,
                    'specialization' => $doctor->specialization,
                ],
                'effective_from_date' => $draftVersion?->effective_from_date?->toDateString(),
                'slot_duration' => $slotDuration,
                'schedules' => $draftSchedules,
            ],
        ];
    }
}
