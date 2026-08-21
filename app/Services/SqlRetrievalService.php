<?php

namespace App\Services;

use App\Models\Doctor;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SqlRetrievalService
{
    private const ALLOWED_TABLES = ['appointments', 'doctors', 'doctor_schedules'];

    private const FORBIDDEN_KEYWORDS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE',
        'CREATE', 'REPLACE', 'GRANT', 'EXEC', 'MERGE', 'RENAME',
        'ATTACH', 'PRAGMA', 'CALL', 'INTO OUTFILE', 'LOAD_FILE',
    ];

    private const SCHEMA_DESCRIPTION = <<<'SCHEMA'
Table: doctors
  - id (integer, primary key)
  - specialization (string, e.g. "Cardiologist", "Dentist", "General Practitioner")
  - years_of_experience (integer)
  - session_price (decimal)
  - gender (string)

Table: doctor_schedules
  - id (integer, primary key)
  - doctor_id (integer, foreign key referencing doctors.id)
  - day_of_week (string, e.g. "Monday", "Tuesday")
  - start_time (time, format HH:MM:SS)
  - end_time (time, format HH:MM:SS)
  - slot_duration (integer, minutes per appointment slot)
  - status (string, "active" or "inactive")

Table: appointments
  - id (integer, primary key)
  - doctor_id (integer, foreign key referencing doctors.id)
  - appointment_date (date, format YYYY-MM-DD)
  - start_time (time, format HH:MM:SS)
  - end_time (time, format HH:MM:SS)
  - status (string, one of: pending, confirmed, completed, cancelled, no_show)
SCHEMA;

    public function retrieve(string $question): ?array
    {
        try {
            $sql = $this->generateSql($question);
        } catch (Throwable $e) {
            Log::warning('RAG: Gemini SQL generation failed', ['error' => $e->getMessage()]);

            return null;
        }

        if ($sql === null) {
            return null;
        }

        if (! $this->isSafe($sql)) {
            Log::warning('RAG: generated SQL rejected by validation', ['sql' => $sql]);

            return null;
        }

        $sql = $this->enforceRowLimit($sql);

        try {
            $rows = DB::select($sql);
            Log::info('RAG: SQL executed', ['sql' => $sql, 'row_count' => count($rows)]);  // ← ضيف هاد

            return DB::select($sql);

        } catch (Throwable $e) {
            Log::warning('RAG: SQL execution failed', ['sql' => $sql, 'error' => $e->getMessage()]);

            return null;
        }
    }

    private function generateSql(string $question): ?string
    {
        $apiKey = trim(config('services.gemini.api_key'));
        $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}";

        $today = now()->toDateString();
        $dayName = now()->format('l');

        $prompt = <<<PROMPT
You are a MySQL query generator for a medical clinic booking system.

Here is the ONLY schema you are allowed to use:
{$this->schemaDescription()}

Here is a reference mapping of doctor names to their doctor_id (use this ONLY
to resolve a doctor's name mentioned in the question to the correct doctor_id
in your WHERE clause — this is not a table, you cannot query it directly):
{$this->doctorNameReference()}

Rules:
- Output ONLY a single MySQL SELECT statement. No explanation, no markdown, no code fences, no semicolon.
- Never use INSERT, UPDATE, DELETE, DROP, ALTER, TRUNCATE or any other write/DDL statement.
- Only reference the tables listed above. Never reference any other table.
- Today's date is {$today} ({$dayName}).
- If the question cannot be answered using only these tables, output exactly: NONE

Question: {$question}
PROMPT;
        $payload = [
            'contents' => [
                ['role' => 'user', 'parts' => [['text' => $prompt]]],
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
            throw new Exception('Gemini SQL generation error: '.$response->body());
        }

        $text = $response->json('candidates.0.content.parts.0.text');

        if (! $text) {
            return null;
        }

        $sql = $this->stripMarkdownFences(trim($text));

        if ($sql === '' || strtoupper($sql) === 'NONE') {
            return null;
        }

        Log::info('RAG: Gemini generated SQL', ['sql' => $sql]);  // ← ضيف هاد

        return $sql;
    }

    private function schemaDescription(): string
    {
        return self::SCHEMA_DESCRIPTION;
    }

    private function doctorNameReference(): string
    {
        $doctors = Doctor::with('user')
            ->get()
            ->map(function (Doctor $doctor) {
                $name = trim(($doctor->user->first_name ?? '').' '.($doctor->user->last_name ?? ''));

                return "{$doctor->id} => Dr. ".($name !== '' ? $name : 'Unknown');
            })
            ->implode("\n");

        return $doctors !== '' ? $doctors : 'No doctors found.';
    }

    private function stripMarkdownFences(string $text): string
    {
        $text = preg_replace('/^```(?:sql)?/i', '', $text);
        $text = preg_replace('/```$/', '', $text);

        return trim($text, "; \t\n\r\0\x0B");
    }

    private function isSafe(string $sql): bool
    {
        $trimmed = trim($sql);

        if ($trimmed === '') {
            return false;
        }

        if (str_contains(rtrim($trimmed, "; \t\n\r"), ';')) {
            return false;
        }

        if (! preg_match('/^SELECT\s/i', $trimmed)) {
            return false;
        }

        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            if (preg_match('/\b'.preg_quote($keyword, '/').'\b/i', $trimmed)) {
                return false;
            }
        }

        preg_match_all('/\b(?:FROM|JOIN)\s+`?(\w+)`?/i', $trimmed, $matches);
        $referencedTables = array_map('strtolower', $matches[1] ?? []);

        if (empty($referencedTables)) {
            return false;
        }

        foreach ($referencedTables as $table) {
            if (! in_array($table, self::ALLOWED_TABLES, true)) {
                return false;
            }
        }

        return true;
    }

    private function enforceRowLimit(string $sql): string
    {
        if (preg_match('/\bLIMIT\s+\d+/i', $sql)) {
            return $sql;
        }

        return $sql.' LIMIT 20';
    }
}
