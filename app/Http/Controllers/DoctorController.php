<?php

namespace App\Http\Controllers;

use App\Actions\Medical\Review\StoreDoctorReviewAction;
use App\Actions\Medical\Stats\GetDoctorDashboardStatsAction;
use App\DTOs\Review\StoreDoctorReviewData;
use App\Http\Filters\V1\DoctorFilter;
use App\Http\Requests\Doctor\StoreDoctorReviewRequest;
use App\Http\Resources\AppointmentResource;
use App\Http\Resources\Api\V1\DoctorResource;
use App\Models\Appointment;
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

        return response()->json([
            'message' => 'Thank you! Your review has been submitted.',
            'data' => [
                'review' => [
                    'id' => $review->id,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                ],
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

    public function summary(Request $request)
    {
        $doctorId = auth()->user()->doctor->id;
        $search = trim((string) $request->query('search', ''));

        $appointments = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->completed()
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('patient.user', function ($patientQuery) use ($search) {
                    $patientQuery->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->with(['patient.user', 'consultation.prescriptionItems'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get();

        $patients = $appointments->groupBy('patient_id')->map(function ($patientAppointments) {
            $latestAppointment = $patientAppointments->first();
            $patient = $latestAppointment->patient->loadMissing('user');

            return [
                'patient_id' => $patient->id,
                'patient_name' => trim(($patient->user->first_name ?? '').' '.($patient->user->last_name ?? '')),
                'completed_appointments_count' => $patientAppointments->count(),
                'last_completed_at' => $latestAppointment->created_at?->format('Y-m-d H:i:s'),
                'patient' => new \App\Http\Resources\PatientResource($patient),
            ];
        })->values();

        return response()->json([
            'type' => 'doctor_summary',
            'data' => [
                'doctor_id' => $doctorId,
                'patients' => $patients,
            ],
            'message' => 'Doctor summary retrieved successfully.',
            'status' => 200,
        ]);
    }

    public function summaryPatientAppointments(Request $request, Patient $patient)
    {
        $doctorId = auth()->user()->doctor->id;
        $date = $request->query('date');

        $appointments = Appointment::query()
            ->where('doctor_id', $doctorId)
            ->where('patient_id', $patient->id)
            ->completed()
            ->when($date, fn ($query) => $query->whereDate('appointment_date', $date))
            ->with(['patient.user', 'consultation.prescriptionItems'])
            ->orderByDesc('appointment_date')
            ->orderByDesc('start_time')
            ->get();

        return response()->json([
            'type' => 'doctor_summary_patient_appointments',
            'data' => [
                'doctor_id' => $doctorId,
                'patient_id' => $patient->id,
                'patient' => new \App\Http\Resources\PatientResource($patient->loadMissing('user')),
                'appointments' => AppointmentResource::collection($appointments),
            ],
            'message' => 'Patient appointments retrieved successfully.',
            'status' => 200,
        ]);
    }
}
