<?php

namespace App\Http\Controllers;

use App\Actions\Medical\Doctor\GetDoctorPatientAppointmentsAction;
use App\Actions\Medical\Doctor\GetDoctorSummaryAction;
use App\Actions\Medical\Review\StoreDoctorReviewAction;
use App\Actions\Medical\Stats\GetDoctorDashboardStatsAction;
use App\DTOs\Review\StoreDoctorReviewData;
use App\Enums\Medical\DoctorSpecialization;
use App\Http\Filters\V1\DoctorFilter;
use App\Http\Requests\Doctor\StoreDoctorReviewRequest;
use App\Http\Resources\Api\V1\DoctorResource;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\PatientResource;
use App\Models\Doctor;
use App\Models\Patient;
use App\Traits\ApiResponses;
use App\Traits\Filterable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller
{
    use ApiResponses, Filterable;

    public function index(DoctorFilter $filter)
    {
        $doctors = Doctor::filter($filter)
            ->whereNotIn('specialization', [
                DoctorSpecialization::RADIOLOGIST->value,
                DoctorSpecialization::PATHOLOGIST->value,
            ])
            ->with('user')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->paginate(10);

        return DoctorResource::collection($doctors);
    }

    public function show(Doctor $doctor)
    {
        return new DoctorResource(
            $doctor->loadMissing('reviews', 'user')
                ->loadAvg('reviews', 'rating')
                ->loadCount('reviews')
        );
    }

    public function storeReview(
        StoreDoctorReviewRequest $request,
        Doctor $doctor,
        StoreDoctorReviewAction $action,
    ) {
        $dto = StoreDoctorReviewData::formRequest($request);

        $review = $action->execute($doctor->id, $dto, $request);

        return $this->success('Thank you! Your review has been submitted.', [
            'review' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
            ],
        ], 201);
    }

    public function doctorReviews(Doctor $doctor)
    {
        $reviews = $doctor->reviews()
            ->with('patient.user')
            ->orderByDesc('created_at')
            ->get();

        $list = $reviews->map(function ($r) {
            return [
                'id' => $r->id,
                'rating' => $r->rating,
                'comment' => $r->comment,
                'patient' => [
                    'name' => $r->patient->user->first_name.' '.$r->patient->user->last_name,
                ],
                'created_at' => $r->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->ok('Doctor reviews retrieved', ['reviews' => $list]);
    }

    public function dashboard(GetDoctorDashboardStatsAction $action)
    {
        $stats = $action->execute(auth()->user()->doctor->id);

        return $this->ok('Dashboard stats retrieved successfully.', [
            'today_appointments' => $stats->todayAppointments,
            'pending_appointments' => $stats->pendingAppointments,
            'completed_today' => $stats->completedToday,
        ]);
    }

    public function summary(Request $request, GetDoctorSummaryAction $action)
    {
        $doctor = auth()->user()->patient;
        if ($doctor) {
            return $this->error('Unauthorized. patient profile found.', 403);
        }

        $search = trim((string) $request->query('search', ''));

        // If doctor_id is not required, pass null to get summary for ALL doctors
        $patients = $action->execute(null, $search);

        return $this->ok('Doctor summary retrieved successfully.', [
            'type' => 'doctor_summary',
            'data' => [
                'doctor_id' => null,
                'patients' => $patients,
            ],
        ]);
    }

    public function summaryPatientAppointments(
        Request $request,
        Patient $patient,
        GetDoctorPatientAppointmentsAction $action,
    ) {
        $date = $request->query('date');
        $search = $request->query('search');
        $specialization = $request->query('specialization');

        // pass null to include appointments across ALL doctors
        $appointments = $action->execute(null, $patient, $date, $search, $specialization);

        return $this->ok('Patient appointments retrieved successfully.', [
            'type' => 'doctor_summary_patient_appointments',
            'data' => [
                'doctor_id' => null,
                'patient_id' => $patient->id,
                'patient' => new PatientResource($patient->loadMissing('user')),
                'appointments' => AppointmentResource::collection($appointments),
            ],
        ]);
    }

    public function specializations()
    {

        $doctorsCount = Doctor::select('specialization', DB::raw('count(*) as count'))
            ->groupBy('specialization')
            ->pluck('count', 'specialization');

        $specializationsStats = collect(DoctorSpecialization::cases())->map(function ($specialization) use (
            $doctorsCount
        ) {
            return [
                'specialization' => $specialization->value,
                'doctors_count' => $doctorsCount->get($specialization->value, 0),
            ];
        });

        return $this->ok('Specializations statistics retrieved successfully.', [
            'data' => $specializationsStats,
        ]);
    }
}
