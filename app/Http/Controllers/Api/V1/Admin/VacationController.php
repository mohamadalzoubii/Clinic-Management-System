<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\Medical\VacationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Vacation\StoreAdminVacationRequest;
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
        $search = $request->query('search');
        $status = $request->query('status');

        if (! $service->vacationsTableExists()) {
            $emptyPaginator = new LengthAwarePaginator([], 0, 10, 1, [
                'path' => $request->url(),
                'query' => $request->query(),
            ]);

            return VacationResource::collection($emptyPaginator);
        }

        $service->syncExpiredVacations();

        $vacations = Vacation::query()
            ->with('doctor.user')
            ->when($status, function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query) use ($search) {
                $query->whereHas('doctor.user', function ($userQuery) use ($search) {
                    $userQuery->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%');
                });
            })
            ->latest('start_date')
            ->paginate(10);

        return VacationResource::collection($vacations);
    }

    public function store(StoreAdminVacationRequest $request, VacationService $service)
    {
        $vacation = $service->createVacation(
            (int) $request->validated('doctor_id'),
            $request->validated(),
            'admin',
            VacationStatus::APPROVED,
        );

        return $this->success('Vacation created successfully.', [
            'vacation' => new VacationResource($vacation->loadMissing('doctor.user')),
        ], 201);
    }

    public function approve(Vacation $vacation, VacationService $service)
    {
        $updatedVacation = $service->approveVacation($vacation);

        return $this->ok('Vacation approved successfully.', [
            'vacation' => new VacationResource($updatedVacation->loadMissing('doctor.user')),
        ]);
    }

    public function decline(Vacation $vacation, VacationService $service)
    {
        $updatedVacation = $service->declineVacation($vacation);

        return $this->ok('Vacation declined successfully.', [
            'vacation' => new VacationResource($updatedVacation->loadMissing('doctor.user')),
        ]);
    }

    public function drop(Vacation $vacation, VacationService $service)
    {
        $updatedVacation = $service->dropVacation($vacation);

        return $this->ok('Vacation dropped successfully.', [
            'vacation' => new VacationResource($updatedVacation->loadMissing('doctor.user')),
        ]);
    }
}