<?php

namespace App\Actions\Medical\Review;

use App\DTOs\Review\StoreDoctorReviewData;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Models\DoctorReview;

class StoreDoctorReviewAction
{
    public function execute(int $patientId, int $doctorId, StoreDoctorReviewData $dto)
    {

        if (! Appointment::HasCompletedVisit($patientId, $doctorId)->exists()) {
            throw new BusinessLogicException('You can only rate doctors after a completed appointment.');
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
