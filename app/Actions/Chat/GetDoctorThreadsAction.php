<?php

namespace App\Actions\Chat;

use App\Enums\Medical\DoctorSpecialization;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetDoctorThreadsAction
{
    public function execute(User $user): array
    {
        $patientId = $user->patient->id;
        $patientUserId = $user->id;

        $lastMessageBodySub = DB::table('messages')
            ->whereIn('conversation_id', fn ($query) => $query->select('id')
                ->from('conversations')
                ->where('patient_id', $patientId)
                ->whereColumn('doctor_id', 'doctors.id')
            )
            ->orderBy('id', 'desc')
            ->select('body')
            ->limit(1);

        $lastMessageTimeSub = DB::table('messages')
            ->whereIn('conversation_id', fn ($query) => $query->select('id')
                ->from('conversations')
                ->where('patient_id', $patientId)
                ->whereColumn('doctor_id', 'doctors.id')
            )
            ->orderBy('id', 'desc')
            ->select('created_at')
            ->limit(1);

        $unreadCountSub = DB::table('messages')
            ->whereIn('conversation_id', fn ($query) => $query->select('id')
                ->from('conversations')
                ->where('patient_id', $patientId)
                ->whereColumn('doctor_id', 'doctors.id')
            )
            ->where('sender_user_id', '!=', $patientUserId)
            ->where('is_read', false)
            ->selectRaw('count(*)');

        return Doctor::with('user')
            ->select('doctors.*')
            ->selectSub($lastMessageBodySub, 'last_message')
            ->selectSub($lastMessageTimeSub, 'last_message_time')
            ->selectSub($unreadCountSub, 'unread_count')
            ->whereNotIn('specialization', [
                DoctorSpecialization::RADIOLOGIST->value,
                DoctorSpecialization::PATHOLOGIST->value,
            ])
            ->get()
            ->all();
    }
}
