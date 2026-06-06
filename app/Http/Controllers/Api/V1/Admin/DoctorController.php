<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Medical\Doctor\GetDoctorAgendaAction;
use App\DTOs\Doctor\StoreDoctorData;
use App\DTOs\Doctor\UpdateDoctorData;
use App\Http\Controllers\Controller;
use App\Http\Filters\V1\DoctorFilter;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use App\Http\Resources\Api\V1\Admin\DoctorResource;
use App\Http\Resources\Api\V1\DoctorReviewResource;
use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Services\DoctorService;
use App\Traits\ApiResponses;

class DoctorController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly DoctorService $doctorService) {}

    public function index(DoctorFilter $filter)
    {
        $doctors = Doctor::filter($filter)
            ->with('user')
            ->withCount('appointments')
            ->withAvg('reviews as rating_average', 'rating')
            ->latest()
            ->paginate(10);

        return DoctorResource::collection($doctors);
    }

    public function show(Doctor $doctor)
    {
        return new DoctorResource($doctor->loadMissing('user', 'reviews'));
    }

    public function agenda(Doctor $doctor, GetDoctorAgendaAction $action)
    {
        $agenda = $action->execute($doctor->id);

        return $this->ok('Doctor agenda retrieved successfully.', [
            'type' => 'doctor_agenda',
            'attributes' => [
                'doctor_id' => $doctor->id,
                'agenda' => $agenda,
            ],
        ]);
    }

    public function reviews(Doctor $doctor)
    {
        $reviews = DoctorReview::query()
            ->where('doctor_id', $doctor->id)
            ->with(['patient.user'])
            ->latest()
            ->paginate(10);

        return DoctorReviewResource::collection($reviews);
    }

    public function store(StoreDoctorRequest $request)
    {
        $dto = StoreDoctorData::formRequest($request);

        $doctor = $this->doctorService->store($dto);

        if ($request->hasFile('photo')) {
            $doctor->addMediaFromRequest('photo')->toMediaCollection('doctor_photo');
        }

        return $this->success('Doctor created successfully.', [
            'doctor' => new DoctorResource($doctor->loadMissing('user')),
        ], 201);
    }

    public function update(UpdateDoctorRequest $request, Doctor $doctor)
    {
        $dto = UpdateDoctorData::formRequest($request);

        $doctor = $this->doctorService->update($doctor, $dto);

        if ($request->hasFile('photo')) {
            $doctor->addMediaFromRequest('photo')->toMediaCollection('doctor_photo');
        }

        return $this->ok('Doctor updated successfully.', [
            'doctor' => new DoctorResource($doctor),
        ]);
    }

    public function destroy(Doctor $doctor)
    {
        $this->doctorService->delete($doctor);

        return $this->ok('Doctor deleted successfully.');
    }
}
