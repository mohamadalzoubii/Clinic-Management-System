<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;

/**
 * Wraps Gemini's embedding endpoint (gemini-embedding-001).
 *
 * Used in two places:
 *  1. The `doctors:generate-embeddings` artisan command — turns each doctor's
 *     bio/specialization/reviews text into a vector, stored on doctors.embedding.
 *  2. DoctorVectorSearchService — embeds the user's chat question the same way,
 *     so it can be compared against the stored doctor vectors.
 *
 * This mirrors the HTTP conventions already used in GeminiChatService
 * (same timeouts/retry/SSL settings) so both services are easy to compare.
 */
class GeminiEmbeddingService
{
    public function embed(string $text): array
    {
        $apiKey = trim(config('services.gemini.api_key'));
        $model = config('services.gemini.embedding_model', 'text-embedding-004');

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$apiKey}";

        $payload = [
            'model' => "models/{$model}",
            'content' => [
                'parts' => [['text' => $text]],
            ],
        ];

        $response = Http::connectTimeout(60)
            ->timeout(60)
            ->retry(3, 2000)
            ->withOptions([
                'verify' => false,
            ])
            ->post($url, $payload);

        if ($response->failed()) {
            throw new Exception('Gemini Embedding Error: '.$response->body());
        }

        return $response->json('embedding.values') ?? [];
    }
}
