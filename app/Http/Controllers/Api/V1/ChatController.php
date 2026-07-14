<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Chat\getConversationMessagesAction;
use App\Actions\Chat\GetDoctorThreadsAction;
use App\Actions\Chat\GetPatientThreadsAction;
use App\Actions\Chat\SendMessageAction;
use App\DTOs\Chat\GetMessagesData;
use App\DTOs\Chat\SendMessageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendMessageRequest;
use App\Http\Resources\Api\V1\DoctorThreadResource;
use App\Http\Resources\Api\V1\PatientThreadResource;
use App\Http\Resources\Api\V1\MessageResource;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class ChatController extends Controller
{
    use ApiResponses;

    public function sendMessage(SendMessageRequest $request, SendMessageAction $action)
    {

        $message = $action->execute($request->user(),
            SendMessageData::fromRequest($request));

        return $this->ok('Message sent successfully', new MessageResource($message));

    }

    public function getMessages(Request $request, getConversationMessagesAction $action, $receiverId)
    {

        $thread = $action->execute($request->user(), new GetMessagesData((int) $receiverId));

        return $this->ok('Messages fetched successfully', [
            'conversation_id' => $thread['conversation_id'],
            'messages' => MessageResource::collection($thread['messages']),
        ]);

    }

    public function getDoctorThreads(Request $request, GetDoctorThreadsAction $action)
    {
        $user = $request->user();

        if (! $user->patient) {
            return $this->error('Unauthorized or Patient profile not found.', 403);
        }

        $doctors = $action->execute($user);

        return $this->ok(
            DoctorThreadResource::collection($doctors),
            'Doctor threads retrieved successfully.'
        );
    }

    public function getPatientThreads(Request $request, GetPatientThreadsAction $action)
    {
        $user = $request->user();

        if (! $user->doctor) {
            return $this->error('Unauthorized or Doctor profile not found.', 403);
        }

        $patients = $action->execute($user);

        return $this->ok(
            PatientThreadResource::collection($patients),
            'Patient threads retrieved successfully.'
        );
    }
}
