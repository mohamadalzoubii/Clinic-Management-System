<?php

use App\Models\Conversation;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('chat.{conversationId}', function ($user, $conversationId) {
    $conversation = Conversation::find($conversationId);
    if (!$conversation) {
        return false;
    }

    $isPatient = $user->patient && (int) $user->patient->id === (int) $conversation->patient_id;
    $isDoctor = $user->doctor && (int) $user->doctor->id === (int) $conversation->doctor_id;

    return $isPatient || $isDoctor;
}, ['guards' => ['sanctum']]); // <--- Crucial 3rd parameter


Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['sanctum']]); // <--- Crucial 3rd parameter