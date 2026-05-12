<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\Chat\SendAiMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MessageResource;
use App\Models\Conversation;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    use ApiResponses;

    public function sendMessage(Request $request, SendAiMessageAction $action)
    {
        $request->validate(['body' => 'required|string|max:1000']);


        $aiMessage = $action->execute($request->user(), $request->input('body'));

        return $this->ok('AI replied successfully', new MessageResource($aiMessage));
    }

    public function getHistory(Request $request)
    {
        $patientId = $request->user()->patient->id;

        $conversation = Conversation::where('patient_id', $patientId)
            ->where('is_ai', true)
            ->first();

        if (!$conversation) {
            return $this->ok('No history found', []);
        }


        $conversation->messages()->whereNull('sender_user_id')->update(['is_read' => true]);

        $messages = $conversation->messages()->orderBy('created_at', 'asc')->get();

        return $this->ok('AI chat history fetched', MessageResource::collection($messages));
    }
}
