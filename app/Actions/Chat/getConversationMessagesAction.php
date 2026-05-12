<?php

namespace App\Actions\Chat;

use App\DTOs\Chat\GetMessagesData;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Services\ChatService;

class getConversationMessagesAction
{
    public function __construct(public readonly ChatService $service) {}

    public function execute(User $user, GetMessagesData $dto)
    {

        [$patientId, $doctorId] = $this->service->resolve($user, $dto->receiverId);

        $conversation = Conversation::Between($doctorId, $patientId)->first();

        if (! $conversation) {
            return Collect([]);
        }

        Message::forConversation($conversation->id)
            ->unreadFromOthers($user->id)
            ->update(['is_read' => true]);

        return Message::forConversation($conversation->id)
            ->orderBy('created_at')
            ->get();

    }
}
