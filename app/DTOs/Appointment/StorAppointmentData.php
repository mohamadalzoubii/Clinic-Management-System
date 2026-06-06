<?php

namespace App\DTOs\Appointment;

use Illuminate\Http\Request;

readonly class StorAppointmentData
{
    public function __construct(
        public int $doctorId,
        public string $date,
        public string $time,
        public ?string $reason,
        public ?array $files,
    ) {}

    public static function formRequest(Request $request): self
    {
        $allFiles = $request->allFiles();
        $uploadedFiles = $allFiles['files'] ?? $allFiles['files_'] ?? null;

        return new self(
            doctorId: (int) $request->validated('doctor_id'),
            date: $request->validated('date'),
            time: $request->validated('time'),
            reason: $request->validated('reason'),
            files: $uploadedFiles,
        );
    }
}
