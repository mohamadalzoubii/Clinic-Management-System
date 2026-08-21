<?php

namespace App\Console\Commands;

use App\Models\Doctor;
use App\Services\GeminiEmbeddingService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Regenerates Gemini embeddings for doctors, used by the RAG chatbot's
 * semantic doctor search (DoctorVectorSearchService).
 *
 * Deliberately a manual/on-demand command, NOT run automatically on every
 * chat request or every doctor update — embeddings only need to change when
 * a doctor's bio, specialization or reviews actually change, so recomputing
 * them per-request would be wasteful and slow.
 *
 * Usage:
 *   php artisan doctors:generate-embeddings            (all doctors)
 *   php artisan doctors:generate-embeddings --doctor=5  (one doctor)
 */
class GenerateDoctorEmbeddings extends Command
{
    protected $signature = 'doctors:generate-embeddings {--doctor= : Only regenerate the embedding for this doctor ID}';

    protected $description = 'Generate/refresh Gemini embeddings for doctors, used by the RAG chatbot for semantic doctor search';

    public function handle(GeminiEmbeddingService $embeddingService): int
    {
        $query = Doctor::query();

        if ($doctorId = $this->option('doctor')) {
            $query->where('id', $doctorId);
        }

        $doctors = $query->get();

        if ($doctors->isEmpty()) {
            $this->warn('No doctors found.');

            return self::SUCCESS;
        }

        $this->info("Generating embeddings for {$doctors->count()} doctor(s)...");
        $bar = $this->output->createProgressBar($doctors->count());
        $bar->start();

        foreach ($doctors as $doctor) {
            try {
                $vector = $embeddingService->embed($doctor->toEmbeddingText());

                if (empty($vector)) {
                    $this->newLine();
                    $this->warn("Doctor #{$doctor->id}: Gemini returned an empty embedding, skipped.");
                } else {
                    // Set directly rather than mass assignment: 'embedding' is
                    // intentionally left out of $fillable so it can never be
                    // set through any admin/doctor update API request.
                    $doctor->embedding = $vector;
                    $doctor->save();
                }
            } catch (Throwable $e) {
                $this->newLine();
                $this->error("Doctor #{$doctor->id}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Done.');

        return self::SUCCESS;
    }
}
