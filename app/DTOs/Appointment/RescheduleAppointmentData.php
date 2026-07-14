<?php

namespace App\DTOs\Appointment;

use Illuminate\Http\Request;

class RescheduleAppointmentData
{
    public function __construct(
        public readonly string $date,
        public readonly string $time,
        public readonly ?string $reason,
    ) {}

    public static function formRequest(Request $request): self
    {
        return new self(
            date: $request->validated('date'),
            time: $request->validated('time'),
            reason: $request->validated('reason'),
        );
    }
}

