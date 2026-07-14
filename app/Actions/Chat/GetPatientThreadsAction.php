<?php

namespace App\Actions\Chat;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class GetPatientThreadsAction
{
    public function execute(User $user): array
    {
        $doctorId = $user->doctor->id;
        $doctorUserId = $user->id;

        $lastMessageBodySub = DB::table('messages')
            ->whereIn('conversation_id', fn ($query) => $query->select('id')
                ->from('conversations')
                ->where('doctor_id', $doctorId)
                ->whereColumn('patient_id', 'patients.id')
            )
            ->orderBy('id', 'desc')
            ->select('body')
            ->limit(1);

        $lastMessageTimeSub = DB::table('messages')
            ->whereIn('conversation_id', fn ($query) => $query->select('id')
                ->from('conversations')
                ->where('doctor_id', $doctorId)
                ->whereColumn('patient_id', 'patients.id')
            )
            ->orderBy('id', 'desc')
            ->select('created_at')
            ->limit(1);

        $unreadCountSub = DB::table('messages')
            ->whereIn('conversation_id', fn ($query) => $query->select('id')
                ->from('conversations')
                ->where('doctor_id', $doctorId)
                ->whereColumn('patient_id', 'patients.id')
            )
            ->where('sender_user_id', '!=', $doctorUserId)
            ->where('is_read', false)
            ->selectRaw('count(*)');

        return Patient::with('user')
            ->select('patients.*')
            ->selectSub($lastMessageBodySub, 'last_message')
            ->selectSub($lastMessageTimeSub, 'last_message_time')
            ->selectSub($unreadCountSub, 'unread_count')
            ->get()
            ->all();
    }
}

