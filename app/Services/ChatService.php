<?php

namespace App\Services;

use App\Exceptions\BusinessLogicException;
use App\Models\Conversation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;

class ChatService
{
    public function resolve(User $user, int $receiverUserId): array
    {
        // 1. جلب حساب المستخدم الطرف الثاني
        $receiverUser = clone $user;
        $receiverUser = User::find($receiverUserId);

        if (! $receiverUser) {
            throw new BusinessLogicException('Receiver user not found.');
        }

        // 2. إذا كان المرسل مريضاً، والمستلم دكتوراً
        if ($user->patient && $receiverUser->doctor) {
            return [$user->patient->id, $receiverUser->doctor->id];
        }

        // 3. إذا كان المرسل دكتوراً، والمستلم مريضاً
        if ($user->doctor && $receiverUser->patient) {
            return [$receiverUser->patient->id, $user->doctor->id];
        }

        // منع دكتور من مراسلة دكتور، أو مريض من مراسلة مريض
        throw new BusinessLogicException('Invalid conversation pairing. Chat must be between a doctor and a patient.');
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
