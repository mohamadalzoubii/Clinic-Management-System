<?php

namespace App\Http\Controllers;

use App\Actions\Medical\Review\StoreDoctorReviewAction;
use App\Actions\Medical\Stats\GetDoctorDashboardStatsAction;
use App\DTOs\Review\StoreDoctorReviewData;
use App\Http\Filters\V1\DoctorFilter;
use App\Http\Requests\Doctor\StoreDoctorReviewRequest;
use App\Http\Resources\Api\V1\DoctorResource;
use App\Models\Doctor;
use App\Traits\ApiResponses;
use App\Traits\Filterable;

class DoctorController extends Controller
{
    use ApiResponses, Filterable;

    public function index(DoctorFilter $filter)
    {
        return DoctorResource::collection(Doctor::filter($filter)->with('user')->paginate(10));
    }

    public function show(Doctor $doctor)
    {
        return new DoctorResource($doctor->loadMissing('reviews', 'user'));
    }

    public function storeReview(StoreDoctorReviewRequest $request, int $doctorId, StoreDoctorReviewAction $action)
    {
        $patient = $request->user()->patient->id;

        $dto = StoreDoctorReviewData::formRequest($request);

        $review = $action->execute($patient, $doctorId, $dto);

        return $this->ok('Thank you! Your review has been submitted.', [
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
            ],
        ], 201);
    }

    public function dashboard(GetDoctorDashboardStatsAction $action)
    {

        $stats = $action->execute(auth()->user()->doctor->id);

        return response()->json([
            'today_appointments' => $stats->todayAppointments,
            'pending_appointments' => $stats->pendingAppointments,
            'completed_today' => $stats->completedToday,
        ]);
    }
}
