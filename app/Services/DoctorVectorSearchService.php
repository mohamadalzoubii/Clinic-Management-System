<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Collection;

class DoctorVectorSearchService
{
    public function __construct(private readonly GeminiEmbeddingService $embeddingService) {}

    public function findRelevantDoctors(string $question, int $limit = 3): Collection
    {
        $queryVector = $this->embeddingService->embed($question);

        if (empty($queryVector)) {
            return collect();
        }

        return Doctor::query()
            ->whereNotNull('embedding')
            ->with('user')
            ->get()
            ->map(function (Doctor $doctor) use ($queryVector) {
                $doctor->similarity_score = $this->cosineSimilarity($queryVector, $doctor->embedding);

                return $doctor;
            })
            ->sortByDesc('similarity_score')
            ->take($limit)
            ->values();
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        if (empty($a) || empty($b) || count($a) !== count($b)) {
            return 0.0;
        }

        $dotProduct = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        foreach ($a as $i => $value) {
            $dotProduct += $value * $b[$i];
            $normA += $value ** 2;
            $normB += $b[$i] ** 2;
        }

        if ($normA === 0.0 || $normB === 0.0) {
            return 0.0;
        }

        return $dotProduct / (sqrt($normA) * sqrt($normB));
    }
}
