<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChatPolicy
{
    use HandlesAuthorization;

    public function startChat(User $user, int $doctorId): bool
    {
        dd([
            'user_id' => $user->id,
            'is_patient' => $user->patient,
            'doctor_id' => $doctorId,
            'has_appointments' => optional($user->patient)->HasAppointmentsWhit($doctorId),
        ]);
        if (! $user->patient()) {
            return false;
        }

        if ($user->doctor()) {
            return true;
        }

        return $user->patient->HasAppointmentsWith($doctorId);
    }
}
