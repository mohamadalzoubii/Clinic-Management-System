<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Actions\Medical\Doctor\GetDoctorAgendaAction;
use App\Http\Controllers\Controller;
use App\Http\Filters\V1\DoctorFilter;
use App\Http\Resources\Api\V1\DoctorReviewResource;
use App\Http\Resources\Api\V1\Admin\DoctorResource;
use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class DoctorController extends Controller
{
    use ApiResponses;

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

    public function store(Request $request)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'specialization' => ['required', 'string', 'max:255'],
            'bio' => ['required', 'string'],
            'education' => ['required', 'string', 'max:255'],
            'certification' => ['required', 'string', 'max:255'],
            'years_of_experience' => ['required', 'integer', 'min:0'],
            'session_price' => ['required', 'numeric', 'min:0'],
            'license_number' => ['required', 'string', 'max:255', 'unique:doctors,license_number'],
            'gender' => ['required', 'string', 'max:50'],
        ]);

        $service = app('App\\Services\\DoctorService');
        $doctor = $service->store($data);

        return $this->success('Doctor created successfully.', [
            'doctor' => new DoctorResource($doctor->loadMissing('user')),
        ], 201);
    }

    public function update(Request $request, Doctor $doctor)
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$doctor->user_id],
            'phone' => ['nullable', 'sometimes', 'string', 'max:20', 'unique:users,phone,'.$doctor->user_id],
            'password' => ['sometimes', 'string', 'min:8'],
            'specialization' => ['sometimes', 'string', 'max:255'],
            'bio' => ['sometimes', 'string'],
            'education' => ['sometimes', 'string', 'max:255'],
            'certification' => ['sometimes', 'string', 'max:255'],
            'years_of_experience' => ['sometimes', 'integer', 'min:0'],
            'session_price' => ['sometimes', 'numeric', 'min:0'],
            'license_number' => ['sometimes', 'string', 'max:255', 'unique:doctors,license_number,'.$doctor->id],
            'gender' => ['sometimes', 'string', 'max:50'],
        ]);

        $service = app('App\\Services\\DoctorService');
        $doctor = $service->update($doctor, $data);

        return $this->ok('Doctor updated successfully.', [
            'doctor' => new DoctorResource($doctor),
        ]);
    }

    public function destroy(Doctor $doctor)
    {
        $service = app('App\\Services\\DoctorService');
        $service->delete($doctor);

        return $this->ok('Doctor deleted successfully.');
    }
}