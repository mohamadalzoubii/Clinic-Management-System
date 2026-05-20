<?php

namespace App\Actions\Chat;

use App\DTOs\Chat\SendMessageData;
use App\Events\MessageSent;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Services\ChatService;

class SendMessageAction
{
    public function __construct(public readonly ChatService $chatService) {}

    public function execute(User $user, SendMessageData $dto)
    {

        [$patientId, $doctorId] = $this->chatService->resolve($user, $dto->receiverId);
        $conversation = $this->chatService->getOrCreateConversation($patientId, $doctorId);

        $message = $conversation->messages()->create([
            'sender_user_id' => $user->id,
            'body' => $dto->body,
            'is_read' => false,
        ]);

        $message->load('conversation');

        broadcast(new MessageSent($message))->toOthers();

        $receiverUser = $this->chatService->getReceiverUser($user, $patientId, $doctorId);
        if ($receiverUser) {
            $receiverUser->notify(new NewMessageNotification($message));
        }

        return $message;

    }
}
