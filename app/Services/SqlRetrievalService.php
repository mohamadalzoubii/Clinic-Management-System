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
    // ← عدّلت الجدولين: طلعنا doctor_schedules (القديم/الميت) وضفنا الجدولين الفعليين
    private const ALLOWED_TABLES = ['appointments', 'doctors', 'doctor_schedule_versions', 'doctor_schedule_version_items'];

    private const FORBIDDEN_KEYWORDS = [
        'INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'TRUNCATE',
        'CREATE', 'REPLACE', 'GRANT', 'EXEC', 'MERGE', 'RENAME',
        'ATTACH', 'PRAGMA', 'CALL', 'INTO OUTFILE', 'LOAD_FILE',
    ];

    // ← الـ schema الجديد بالكامل، بيعكس الموديلات الفعلية يلي بيستخدمها نظام الحجز
    private const SCHEMA_DESCRIPTION = <<<'SCHEMA'
Table: doctors
  - id (integer, primary key)
  - specialization (string, e.g. "Cardiologist", "Dentist", "General Practitioner")
  - years_of_experience (integer)
  - session_price (decimal)
  - gender (string)

Table: doctor_schedule_versions
  - id (integer, primary key)
  - doctor_id (integer, foreign key referencing doctors.id)
  - effective_from_date (date, format YYYY-MM-DD) -- the date this schedule version starts applying
  - created_by_admin_id (integer)

Table: doctor_schedule_version_items
  - id (integer, primary key)
  - doctor_schedule_version_id (integer, foreign key referencing doctor_schedule_versions.id)
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

IMPORTANT — how doctor schedules work:
A doctor can have MULTIPLE schedule versions over time (doctor_schedule_versions), each with its
own effective_from_date. A version applies starting from that date until a newer version's
effective_from_date is reached. This means the CURRENT working days/hours for a doctor are NEVER
found by just reading all doctor_schedule_version_items for that doctor — you must first find the
doctor's CURRENTLY ACTIVE version (the one with the latest effective_from_date that is <= today),
and only read items belonging to THAT version.

To find the current working days/hours for a doctor, always use this pattern:

SELECT dswi.day_of_week, dswi.start_time, dswi.end_time, dswi.slot_duration
FROM doctor_schedule_version_items dswi
JOIN doctor_schedule_versions dsv ON dsv.id = dswi.doctor_schedule_version_id
WHERE dsv.doctor_id = <doctor_id>
  AND dswi.status = 'active'
  AND dsv.effective_from_date = (
      SELECT MAX(effective_from_date)
      FROM doctor_schedule_versions
      WHERE doctor_id = <doctor_id>
        AND effective_from_date <= CURDATE()
  )

Never read doctor_schedule_version_items without joining/filtering by the doctor's currently
active doctor_schedule_versions row as shown above — reading all versions/items for a doctor
mixes past, current, and possibly future schedules together and gives wrong availability.
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
            Log::info('RAG: SQL executed', ['sql' => $sql, 'row_count' => count($rows)]);

            return $rows; // ← كان فيها DB::select($sql) مرتين (تنفيذ مزدوج بدون داعي)، صلّحتها

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
- When the question is about a doctor's working days/hours/availability, you MUST follow the
  "IMPORTANT" pattern described in the schema above (filter to the currently active schedule
  version only). Do not select from doctor_schedule_version_items without that filter.
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

        Log::info('RAG: Gemini generated SQL', ['sql' => $sql]);

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
