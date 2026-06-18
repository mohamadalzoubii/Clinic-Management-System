<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

class GeminiChatService
{
    public function generateReply(array $chatHistory): string
    {
        $apiKey = trim(config('services.gemini.api_key'));

        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $systemInstruction = [
            'parts' => [
                ['text' => 'You are "Medics AI", a dedicated and professional medical assistant developed specifically for the "Medics" Clinical Management System. ' .
                    'Your main job is to support patients by answering their health-related questions accurately, politely, and concisely while they wait for their doctor. ' .
                    'CRITICAL RULE: You must clearly state that you are an AI medical assistant, NOT a real doctor, and your advice is a temporary guidance or consultation until the clinic\'s doctor responds to them. ' .
                    'Always emphasize that for serious, emergency, or definitive medical conditions, they must see their physical doctor immediately. ' .
                    'Please respond in a friendly manner and in the exact same language or dialect the user is using (if they speak in Arabic/Syrian, reply in warm, helpful Arabic).'],
            ],
        ];

        $payload = [
            'system_instruction' => $systemInstruction,
            'contents' => $chatHistory,
        ];


        $response = Http::connectTimeout(30)
            ->timeout(60)
            ->retry(3, 2000)
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
