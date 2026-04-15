<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (! $conversation) {
        return false;
    }

    $isPatient = $user->patient && $user->patient->id === $conversation->patient_id;
    $isDoctor = $user->doctor && $user->doctor->id === $conversation->doctor_id;

    return $isPatient || $isDoctor;
});
