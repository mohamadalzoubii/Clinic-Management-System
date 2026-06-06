<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Admin\GetDoctorScheduleAction;
use App\Actions\Admin\GetDoctorSchedulesAction;
use App\Actions\Admin\UpdateDoctorScheduleAction;
use App\DTOs\Admin\ScheduleData;
use App\DTOs\Admin\ScheduleIndexData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateScheduleRequest;
use App\Models\Doctor;
use App\Services\Medical\DoctorScheduleVersionService;
use App\Traits\ApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ScheduleController extends Controller
{
    use ApiResponses;

    public function index(Request $request, GetDoctorSchedulesAction $action): JsonResponse
    {
        $data = ScheduleIndexData::fromRequest($request);
        $schedules = $action->execute($data);

        return $this->ok('Schedules retrieved successfully.', $schedules);
    }

    public function show(Doctor $doctor, GetDoctorScheduleAction $action): JsonResponse
    {
        $result = $action->execute($doctor);

        return $this->ok('Doctor schedules retrieved successfully.', $result);
    }

    public function update(
        UpdateScheduleRequest $request,
        Doctor $doctor,
        UpdateDoctorScheduleAction $action,
        DoctorScheduleVersionService $versionService
    ): JsonResponse {
        $dto = ScheduleData::fromRequest($request);
        $result = $action->execute($doctor, $dto, $request->user()?->id);

        return $this->ok('Doctor schedule saved successfully. New availability applies from tomorrow.', [
            'type' => 'doctor_schedule_editor',
            'attributes' => [
                'doctor_id' => $doctor->id,
                'effective_from_date' => $result['effective_from_date'],
                'slot_duration' => $dto->slotDuration,
                'schedules' => $versionService->formatItems($result['version']),
            ],
        ]);
    }
}
