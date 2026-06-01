<?php

namespace App\Services\Medical;

use App\Enums\Medical\DayOfWeek;
use App\Enums\Medical\ScheduleStatus;
use App\Models\Doctor;
use App\Models\DoctorScheduleVersion;
use App\Models\DoctorScheduleVersionItem;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DoctorScheduleVersionService
{
    public function versionsForDoctor(int $doctorId): Collection
    {
        return DoctorScheduleVersion::query()
            ->forDoctor($doctorId)
            ->with(['items'])
            ->orderBy('effective_from_date')
            ->orderBy('id')
            ->get();
    }

    public function resolveVersionForDate(int $doctorId, Carbon|string $date): ?DoctorScheduleVersion
    {
        return $this->resolveVersionFromCollection($this->versionsForDoctor($doctorId), $date);
    }

    public function resolveCurrentVersion(int $doctorId): ?DoctorScheduleVersion
    {
        return $this->resolveVersionForDate($doctorId, Carbon::today());
    }

    public function resolveLatestVersionForEditor(int $doctorId): ?DoctorScheduleVersion
    {
        return DoctorScheduleVersion::query()
            ->forDoctor($doctorId)
            ->with(['items'])
            ->orderByDesc('effective_from_date')
            ->orderByDesc('id')
            ->first();
    }

    public function resolveVersionFromCollection(Collection $versions, Carbon|string $date): ?DoctorScheduleVersion
    {
        $dateString = Carbon::parse($date)->toDateString();

        return $versions
            ->filter(fn (DoctorScheduleVersion $version) => $version->effective_from_date?->toDateString() <= $dateString)
            ->sort(function (DoctorScheduleVersion $left, DoctorScheduleVersion $right) {
                $dateCompare = strcmp(
                    $right->effective_from_date?->toDateString() ?? '',
                    $left->effective_from_date?->toDateString() ?? ''
                );

                if ($dateCompare !== 0) {
                    return $dateCompare;
                }

                return $right->id <=> $left->id;
            })
            ->first();
    }

    public function resolveItemForDate(int $doctorId, Carbon|string $date): ?DoctorScheduleVersionItem
    {
        return $this->resolveItemFromCollection($this->versionsForDoctor($doctorId), $date);
    }

    public function resolveItemFromCollection(Collection $versions, Carbon|string $date): ?DoctorScheduleVersionItem
    {
        $parsedDate = Carbon::parse($date);
        $dayName = strtolower($parsedDate->englishDayOfWeek);
        $version = $this->resolveVersionFromCollection($versions, $parsedDate);

        if (! $version) {
            return null;
        }

        return $version->items
            ->first(function (DoctorScheduleVersionItem $item) use ($dayName) {
                return $item->status === ScheduleStatus::ACTIVE && strtolower($item->day_of_week->value ?? (string) $item->day_of_week) === $dayName;
            });
    }

    public function workingDaysForDate(int $doctorId, Carbon|string $date): array
    {
        return $this->workingDaysFromCollection($this->versionsForDoctor($doctorId), $date);
    }

    public function workingDaysFromCollection(Collection $versions, Carbon|string $date): array
    {
        $version = $this->resolveVersionFromCollection($versions, $date);

        if (! $version) {
            return [];
        }

        return $version->items
            ->filter(fn (DoctorScheduleVersionItem $item) => $item->status === ScheduleStatus::ACTIVE)
            ->pluck('day_of_week')
            ->map(fn ($day) => strtolower($day->value ?? $day))
            ->values()
            ->all();
    }

    public function createVersionFromSchedules(
        Doctor $doctor,
        array $schedules,
        int $slotDuration,
        Carbon|string $effectiveFromDate,
        ?int $adminId = null,
    ): DoctorScheduleVersion {
        return DB::transaction(function () use ($doctor, $schedules, $slotDuration, $effectiveFromDate, $adminId) {
            $version = DoctorScheduleVersion::create([
                'doctor_id' => $doctor->id,
                'effective_from_date' => Carbon::parse($effectiveFromDate)->toDateString(),
                'created_by_admin_id' => $adminId,
            ]);

            $items = collect($schedules)
                ->map(function (array $scheduleData) use ($version, $slotDuration) {
                    return [
                        'doctor_schedule_version_id' => $version->id,
                        'day_of_week' => strtolower($scheduleData['day_of_week']),
                        'start_time' => $scheduleData['start_time'],
                        'end_time' => $scheduleData['end_time'],
                        'slot_duration' => $slotDuration,
                        'status' => ScheduleStatus::ACTIVE->value,
                    ];
                })
                ->all();

            if (! empty($items)) {
                $version->items()->createMany($items);
            }

            return $version->load('items');
        });
    }

    public function formatItems(DoctorScheduleVersion $version): array
    {
        return $version->items
            ->filter(fn (DoctorScheduleVersionItem $item) => $item->status === ScheduleStatus::ACTIVE)
            ->sortBy(fn (DoctorScheduleVersionItem $item) => array_search(strtolower($item->day_of_week->value ?? (string) $item->day_of_week), $this->dayOrder(), true))
            ->values()
            ->map(function (DoctorScheduleVersionItem $item) {
                return [
                    'id' => $item->id,
                    'day_of_week' => strtolower($item->day_of_week->value ?? (string) $item->day_of_week),
                    'start_time' => Carbon::parse($item->start_time)->format('H:i'),
                    'end_time' => Carbon::parse($item->end_time)->format('H:i'),
                    'slot_duration' => (int) $item->slot_duration,
                    'status' => strtolower($item->status->value ?? (string) $item->status),
                ];
            })
            ->all();
    }

    public function effectiveFromDateForAgendaWindow(int $daysAhead = 7): Carbon
    {
        return Carbon::tomorrow();
    }

    private function dayOrder(): array
    {
        return array_map(fn (DayOfWeek $day) => $day->value, DayOfWeek::cases());
    }
}