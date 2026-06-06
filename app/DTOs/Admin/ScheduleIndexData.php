<?php

namespace App\DTOs\Admin;

use Illuminate\Http\Request;

class ScheduleIndexData
{
    public function __construct(
        public ?string $search,
        public ?string $doctorId,
        public ?string $status
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            search: $request->query('search'),
            doctorId: $request->query('doctor_id'),
            status: $request->query('status')
        );
    }
}
