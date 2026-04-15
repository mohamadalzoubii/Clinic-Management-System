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

class ChatController extends Controller
{
    use ApiResponses;

    public function sendMessage(SendMessageRequest $request, SendMessageAction $action)
    {

        $message = $action->execute($request->user(),
            SendMessageData::fromRequest($request));

        return $this->ok('Message sent successfully', $message);

    }

    public function getMessages(Request $request, GetConversationMessagesAction $action, $receiverId)
    {

        $messages = $action->execute($request->user(), new GetMessagesData((int) $receiverId));

        return $this->ok('Messages fetched successfully', MessageResource::collection($messages));

    }
}
