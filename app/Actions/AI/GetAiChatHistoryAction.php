<?php

namespace App\Actions\AI;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GetAiChatHistoryAction
{
    public function execute(User $user): Collection
    {
        $patientId = $user->patient->id;

        $conversation = Conversation::query()
            ->where('patient_id', $patientId)
            ->where('is_ai', true)
            ->first();

        if (! $conversation) {
            return Conversation::query()->whereRaw('0 = 1')->get();
        }

        $conversation->messages()
            ->whereNull('sender_user_id')
            ->update(['is_read' => true]);

        return $conversation->messages()
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
