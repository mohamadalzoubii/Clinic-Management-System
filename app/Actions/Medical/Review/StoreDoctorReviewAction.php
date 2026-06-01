<?php

namespace App\Actions\Medical\Review;

use App\DTOs\Review\StoreDoctorReviewData;
use App\Exceptions\BusinessLogicException;
use App\Enums\Medical\AppointmentStatus;
use App\Models\Appointment;
use App\Models\DoctorReview;

class StoreDoctorReviewAction
{
    public function execute(int $patientId, int $doctorId, StoreDoctorReviewData $dto)
    {

        $completedAppointmentsCount = Appointment::query()
            ->where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->where('status', AppointmentStatus::COMPLETED)
            ->count();

        if ($completedAppointmentsCount < 3) {
            throw new BusinessLogicException('You need at least 3 completed appointments with this doctor before leaving a review.');
        }

        if (DoctorReview::ExistsFor($patientId, $doctorId)->exists()) {
            throw new BusinessLogicException('You have already rated this doctor');
        }

        return DoctorReview::create([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
            'comment' => $dto->comment,
            'rating' => $dto->rating,
        ]);
    }
}
