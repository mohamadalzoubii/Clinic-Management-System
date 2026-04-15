<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Conversation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;

class ChatService
{
    public function resolve(User $user, int $receiverId): array
    {
        if ($user->patient) {
            return [$user->patient->id, $receiverId];
        }

        if ($user->doctor) {
            return [$receiverId, $user->doctor->id];
        }

        throw new BusinessLogicException('Unauthorized user type.');
    }

    public function getOrCreateConversation(int $patientId, int $doctorId): Conversation
    {
        return Conversation::firstOrCreate([
            'patient_id' => $patientId,
            'doctor_id' => $doctorId,
        ]);
    }

    public function getReceiverUser(User $sender, int $patientId, int $doctorId): ?User
    {
        if ($sender->doctor) {
            return Patient::find($patientId)?->user;
        }

        if ($sender->patient) {
            return Doctor::find($doctorId)?->user;
        }

        return null;
    }
}
