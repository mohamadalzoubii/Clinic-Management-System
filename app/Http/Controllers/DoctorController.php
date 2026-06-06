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
            ->paginate(10);

        return DoctorResource::collection($doctors);
    }

    public function show(Doctor $doctor)
    {
        return new DoctorResource($doctor->loadMissing('reviews', 'user'));
    }

    public function storeReview(
        StoreDoctorReviewRequest $request,
        int $doctorId,
        StoreDoctorReviewAction $action,
    ) {
        $dto = StoreDoctorReviewData::formRequest($request);

        $review = $action->execute($doctorId, $dto, $request);

        return $this->success('Thank you! Your review has been submitted.', [
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

        return $this->ok('Dashboard stats retrieved successfully.', [
            'today_appointments' => $stats->todayAppointments,
            'pending_appointments' => $stats->pendingAppointments,
            'completed_today' => $stats->completedToday,
        ]);
    }

    public function summary(Request $request, GetDoctorSummaryAction $action)
    {
        $doctor = auth()->user()->doctor;
        if (!$doctor) {
            return $this->error('Unauthorized. Doctor profile not found.', 403);
        }

        $doctorId = $doctor->id;
        $search = trim((string) $request->query('search', ''));

        $patients = $action->execute($doctorId, $search);

        return $this->ok('Doctor summary retrieved successfully.', [
            'type' => 'doctor_summary',
            'data' => [
                'doctor_id' => $doctorId,
                'patients' => $patients,
            ],
        ]);
    }

    public function summaryPatientAppointments(
        Request $request,
        Patient $patient,
        GetDoctorPatientAppointmentsAction $action,
    ) {
        $doctorId = auth()->user()->doctor->id;
        $date = $request->query('date');

        $appointments = $action->execute($doctorId, $patient, $date);

        return $this->ok('Patient appointments retrieved successfully.', [
            'type' => 'doctor_summary_patient_appointments',
            'data' => [
                'doctor_id' => $doctorId,
                'patient_id' => $patient->id,
                'patient' => new PatientResource($patient->loadMissing('user')),
                'appointments' => AppointmentResource::collection($appointments),
            ],
        ]);
    }
}
