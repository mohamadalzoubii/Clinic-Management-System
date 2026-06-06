<?php

namespace App\DTOs\Admin;

use App\Http\Requests\Admin\UpdateScheduleRequest;

class ScheduleData
{
    public function __construct(
        public int $slotDuration,
        public array $schedules
    ) {}

    public static function fromRequest(UpdateScheduleRequest $request): self
    {
        return new self(
            slotDuration: (int) $request->validated('slot_duration'),
            schedules: $request->validated('schedules')
        );
    }
}
