<?php

namespace App\Actions\Medical\Review;

use App\DTOs\Review\StoreDoctorReviewData;
use App\Enums\Medical\AppointmentStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Models\DoctorReview;
use Illuminate\Http\Request;

class StoreDoctorReviewAction
{
    public function execute(int $doctorId, StoreDoctorReviewData $dto, Request $request): DoctorReview
    {
        $patient = $request->user()->patient;

        if (! $patient) {
            throw new BusinessLogicException('Only patients can submit reviews.');
        }

        $patientId = $patient->id;

        $completedAppointmentsCount = Appointment::query()
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->where('status', AppointmentStatus::COMPLETED)
            ->count();

        if ($completedAppointmentsCount < 3) {
            throw new BusinessLogicException('You need at least 3 completed appointments with this doctor before leaving a review.');
        }



        return DoctorReview::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'comment' => $dto->comment,
            'rating' => $dto->rating,
        ]);
    }
}
