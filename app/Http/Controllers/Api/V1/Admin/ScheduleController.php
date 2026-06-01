<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\Medical\DayOfWeek;
use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Services\Medical\DoctorScheduleVersionService;
use App\Traits\ApiResponses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ScheduleController extends Controller
{
    use ApiResponses;

    public function index(Request $request)
    {
        $search = $request->query('search');
        $doctorId = $request->query('doctor_id');
        $status = $request->query('status');

        $schedules = DoctorSchedule::with(['doctor.user'])
            ->when($doctorId && $doctorId !== 'all', function ($query) use ($doctorId) {
                $query->where('doctor_id', $doctorId);
            })
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                $query->whereHas('doctor.user', function ($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->latest()
            ->paginate(10);

        return response()->json($schedules);
    }

    public function show(Doctor $doctor, DoctorScheduleVersionService $versionService)
    {
        $doctor->loadMissing('user');

        $currentVersion = $versionService->resolveCurrentVersion($doctor->id);
        $currentSchedules = $currentVersion ? $versionService->formatItems($currentVersion) : [];

        // Also include the latest saved draft (editable) so the UI can show the saved draft separately
        $draftVersion = $versionService->resolveLatestVersionForEditor($doctor->id);
        $draftSchedules = $draftVersion ? $versionService->formatItems($draftVersion) : [];

        $slotDuration = $draftSchedules[0]['slot_duration'] ?? $currentSchedules[0]['slot_duration'] ?? 30;

        return $this->ok('Doctor schedules retrieved successfully.', [
            'type' => 'doctor_schedule_editor',
            'attributes' => [
                'doctor' => [
                    'id' => $doctor->id,
                    'first_name' => $doctor->user?->first_name,
                    'last_name' => $doctor->user?->last_name,
                    'specialization' => $doctor->specialization,
                ],
                // Return the newest (draft) schedule as the primary `schedules` payload so the editor shows it
                'effective_from_date' => $draftVersion?->effective_from_date?->toDateString(),
                'slot_duration' => $slotDuration,
                'schedules' => $draftSchedules,
            ],
        ]);
    }

    public function update(Request $request, Doctor $doctor, DoctorScheduleVersionService $versionService, \App\Actions\Medical\Doctor\GetDoctorAgendaAction $agendaAction)
    {
        $validated = $request->validate([
            'slot_duration' => ['required', 'integer', 'min:1', 'max:480'],
            'schedules' => ['required', 'array', 'min:1'],
            'schedules.*.day_of_week' => ['required', 'string', Rule::in($this->dayOrder())],
            'schedules.*.start_time' => ['required', 'date_format:H:i'],
            'schedules.*.end_time' => ['required', 'date_format:H:i'],
        ]);

        $scheduleErrors = [];

        foreach ($validated['schedules'] as $index => $scheduleData) {
            if ($scheduleData['end_time'] <= $scheduleData['start_time']) {
                $scheduleErrors["schedules.$index.end_time"] = [
                    'End time must be after start time for '.strtoupper($scheduleData['day_of_week']).'.',
                ];
            }
        }

        if (! empty($scheduleErrors)) {
            throw ValidationException::withMessages($scheduleErrors);
        }

        // Make the new version effective after the last day currently shown in the 7-day agenda
        $agenda = $agendaAction->execute($doctor->id, 7);
        if (! empty($agenda)) {
            $lastDate = end($agenda)['full_date'] ?? null;
            $effectiveFromDate = $lastDate ? Carbon::parse($lastDate)->addDay()->toDateString() : Carbon::tomorrow()->toDateString();
        } else {
            $effectiveFromDate = Carbon::tomorrow()->toDateString();
        }
        $adminId = $request->user()?->id;

        $version = $versionService->createVersionFromSchedules(
            $doctor,
            $validated['schedules'],
            (int) $validated['slot_duration'],
            $effectiveFromDate,
            $adminId,
        );

        $selectedDays = collect($validated['schedules'])
            ->pluck('day_of_week')
            ->map(fn ($day) => strtolower($day))
            ->unique()
            ->values()
            ->all();

        Log::info('Doctor working schedule updated.', [
            'doctor_id' => $doctor->id,
            'version_id' => $version->id,
            'effective_from_date' => $effectiveFromDate,
            'slot_duration' => $validated['slot_duration'],
            'selected_days' => $selectedDays,
            'schedules' => $validated['schedules'],
        ]);

        return $this->ok('Doctor schedule saved successfully. New availability applies from tomorrow.', [
            'type' => 'doctor_schedule_editor',
            'attributes' => [
                'doctor_id' => $doctor->id,
                'effective_from_date' => $effectiveFromDate,
                'slot_duration' => $validated['slot_duration'],
                'schedules' => $versionService->formatItems($version),
            ],
        ]);
    }

    private function dayOrder(): array
    {
        return array_map(fn (DayOfWeek $day) => $day->value, DayOfWeek::cases());
    }
}