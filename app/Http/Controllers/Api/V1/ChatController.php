<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Chat\getConversationMessagesAction;
use App\Actions\Chat\SendMessageAction;
use App\DTOs\Chat\GetMessagesData;
use App\DTOs\Chat\SendMessageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use App\Http\Resources\Api\V1\DoctorThreadResource;
use App\Models\Doctor;
use Illuminate\Support\Facades\DB;
use App\Enums\Medical\DoctorSpecialization;

class ChatController extends Controller
{
    use ApiResponses;

    public function sendMessage(SendMessageRequest $request, SendMessageAction $action)
    {

        $message = $action->execute($request->user(),
            SendMessageData::fromRequest($request));

        return $this->ok('Message sent successfully', new MessageResource($message));

    }

    public function getMessages(Request $request, GetConversationMessagesAction $action, $receiverId)
    {

        $thread = $action->execute($request->user(), new GetMessagesData((int) $receiverId));

        return $this->ok('Messages fetched successfully', [
            'conversation_id' => $thread['conversation_id'],
            'messages' => MessageResource::collection($thread['messages']),
        ]);

    }
    
   public function getDoctorThreads(Request $request)
    {
        $user = $request->user();
        
        // Ensure the user has a patient record
        if (!$user->patient) {
            return response()->json(['message' => 'Unauthorized or Patient profile not found.'], 403);
        }
        
        $patientId = $user->patient->id;
        $patientUserId = $user->id;

        // 1. Subquery to find the conversation ID between this doctor and the patient
        $conversationSub = DB::table('conversations')
            ->where('patient_id', $patientId)
            ->whereColumn('doctor_id', 'doctors.id')
            ->select('id')
            ->limit(1);

        // 2. Subquery for the latest message body
        $lastMessageBodySub = DB::table('messages')
            ->whereIn('conversation_id', fn($query) => $query->select('id')
                ->from('conversations')
                ->where('patient_id', $patientId)
                ->whereColumn('doctor_id', 'doctors.id')
            )
            ->orderBy('id', 'desc')
            ->select('body')
            ->limit(1);

        // 3. Subquery for the latest message timestamp
        $lastMessageTimeSub = DB::table('messages')
            ->whereIn('conversation_id', fn($query) => $query->select('id')
                ->from('conversations')
                ->where('patient_id', $patientId)
                ->whereColumn('doctor_id', 'doctors.id')
            )
            ->orderBy('id', 'desc')
            ->select('created_at')
            ->limit(1);

        // 4. Subquery for unread message counts (sent by others, unread)
        $unreadCountSub = DB::table('messages')
            ->whereIn('conversation_id', fn($query) => $query->select('id')
                ->from('conversations')
                ->where('patient_id', $patientId)
                ->whereColumn('doctor_id', 'doctors.id')
            )
            ->where('sender_user_id', '!=', $patientUserId)
            ->where('is_read', false)
            ->selectRaw('count(*)');

        // Fetch filtered doctors with their subquery attributes injected
        $doctors = Doctor::with('user')
            ->select('doctors.*')
            ->selectSub($lastMessageBodySub, 'last_message')
            ->selectSub($lastMessageTimeSub, 'last_message_time')
            ->selectSub($unreadCountSub, 'unread_count')
            // Exclude specified doctor specializations from the results
            ->whereNotIn('specialization', [
                DoctorSpecialization::RADIOLOGIST->value,
                DoctorSpecialization::PATHOLOGIST->value,
            ])
            ->get();

        return DoctorThreadResource::collection($doctors);
    }
}
