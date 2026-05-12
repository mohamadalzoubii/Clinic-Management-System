<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GeminiChatService
{
    public function generateReply(array $chatHistory): string
    {

        $apiKey = trim(config('services.gemini.api_key'));

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}";
        $systemInstruction = [
            'parts' => [
                ['text' => 'You are a professional, empathetic, and helpful medical AI assistant. Answer the patient\'s health-related questions accurately but concisely. Always remind them that you are an AI and they should consult a real doctor for serious conditions. Please respond in the same language the user uses.'],
            ],
        ];

        $payload = [
            'system_instruction' => $systemInstruction,
            'contents' => $chatHistory,
        ];

        $response = Http::connectTimeout(30)
            ->timeout(60)
            ->withOptions([
                'verify' => false,
            ])
            ->post($url, $payload);
        if ($response->failed()) {

            throw new Exception('Gemini Error: '.$response->body());
        }

        return $response->json('candidates.0.content.parts.0.text') ?? 'Sorry, I am unable to process your request right now.';
    }
}
