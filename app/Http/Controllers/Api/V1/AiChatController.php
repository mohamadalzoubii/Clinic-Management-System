<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\AI\GetAiChatHistoryAction;
use App\Actions\Chat\SendAiMessageAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendAiMessageRequest;
use App\Http\Resources\Api\V1\MessageResource;
use App\Traits\ApiResponses;

class AiChatController extends Controller
{
    use ApiResponses;

    public function sendMessage(SendAiMessageRequest $request, SendAiMessageAction $action)
    {
        $aiMessage = $action->execute($request->user(), $request->validated('body'));

        return $this->ok('AI replied successfully', new MessageResource($aiMessage));
    }

    public function getHistory(GetAiChatHistoryAction $action)
    {
        $messages = $action->execute(auth()->user());

        return $this->ok('AI chat history fetched', MessageResource::collection($messages)->resolve());
    }
}
