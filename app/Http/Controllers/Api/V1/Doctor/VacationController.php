<?php

namespace App\Http\Controllers\Api\V1\Doctor;

use App\Enums\Medical\VacationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vacation\StoreDoctorVacationRequest;
use App\Http\Resources\Api\V1\VacationResource;
use App\Models\Vacation;
use App\Services\VacationService;
use App\Traits\ApiResponses;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class VacationController extends Controller
{
    use ApiResponses;

    public function index(Request $request, VacationService $service)
    {
        $doctorId = (int) $request->user()->doctor->id;

        if (! $service->vacationsTableExists()) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 10, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return VacationResource::collection($emptyPaginator);
        }

        $service->syncExpiredVacations($doctorId);

        $vacations = Vacation::query()
            ->with('doctor.user')
            ->where('doctor_id', $doctorId)
            ->latest('start_date')
            ->paginate(10);

        return VacationResource::collection($vacations);
    }

    public function store(StoreDoctorVacationRequest $request, VacationService $service)
    {
        $doctorId = (int) $request->user()->doctor->id;

        $vacation = $service->createVacation(
            $doctorId,
            $request->validated(),
            'doctor',
            VacationStatus::PENDING,
        );

        return $this->success('Vacation request submitted successfully.', [
            'vacation' => new VacationResource($vacation->loadMissing('doctor.user')),
        ], 201);
    }

    public function constraints(int $doctor, VacationService $service)
    {
        $earliestAllowed = $service->earliestAllowedStartDate($doctor);
        $lastAppointmentDay = $service->lastAppointmentDay($doctor);

        return $this->ok('Vacation constraints retrieved successfully.', [
            'type' => 'vacation_constraints',
            'attributes' => [
                'doctor_id' => $doctor,
                'last_appointment_day' => $lastAppointmentDay?->toDateString(),
                'earliest_start_date' => $earliestAllowed->toDateString(),
                'minimum_lead_days' => 14,
            ],
        ]);
    }
}