<?php

namespace App\DTOs\Appointment;

use Illuminate\Http\Request;

class StorAppointmentData
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly int $doctorId,
        public readonly string $date,
        public readonly string $time,
        public readonly ?string $reason,
        public readonly ?array $files,
    ) {}

    public static function formRequset(Request $request): self
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
