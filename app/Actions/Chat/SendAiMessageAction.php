<?php

namespace App\Actions\Chat;

namespace App\Actions\Chat;

use App\Models\Conversation;
use App\Models\User;
use App\Services\RagService;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendAiMessageAction
{
    public function __construct(public readonly RagService $ragService) {}

    public function execute(User $user, string $messageBody)
    {
        $patientId = $user->patient->id;

        $conversation = Conversation::firstOrCreate([
            'patient_id' => $patientId,
            'is_ai' => true,
            'doctor_id' => null,
        ]);

        $conversation->messages()->create([
            'sender_user_id' => $user->id,
            'body' => $messageBody,
            'is_read' => true,
        ]);

        $recentMessages = $conversation->messages()->latest()->take(5)->get()->reverse();

        $chatHistory = $recentMessages->map(function ($msg) {
            return [
                'role' => $msg->sender_user_id ? 'user' : 'model',
                'parts' => [['text' => $msg->body]],
            ];
        })->values()->toArray();

        try {
            $aiReplyText = $this->ragService->answer($messageBody, $chatHistory);
        } catch (Throwable $e) {
            Log::error('AI chat pipeline failed unexpectedly', ['error' => $e->getMessage()]);
            $aiReplyText = 'عذراً، حدث خطأ غير متوقع. الرجاء المحاولة مرة أخرى لاحقاً.';
        }

        $aiMessage = $conversation->messages()->create([
            'sender_user_id' => null,
            'body' => $aiReplyText,
            'is_read' => false,
        ]);

        return $aiMessage;
    }
}
