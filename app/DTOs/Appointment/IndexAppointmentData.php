<?php

namespace App\DTOs\Appointment;

use Illuminate\Http\Request;

class IndexAppointmentData
{
    public function __construct(
        public readonly int $perPage,
        public readonly ?string $search = null,
        public readonly ?int $partyId = null,
        public readonly ?string $partyType = null,
        public readonly ?string $status = null,
        public readonly ?string $date = null,
        public readonly ?string $specialization = null,
        public readonly ?string $sort = null,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            perPage: max(1, (int) $request->validated('limit', 10)),
            search: $request->validated('search'),
            partyId: $request->validated('party_id') !== null
                ? (int) $request->validated('party_id')
                : null,
            partyType: $request->validated('party_type'),
            status: $request->validated('status'),
            date: $request->validated('date'),
            specialization: $request->validated('specialization'),
            sort: $request->validated('sort'),
        );
    }
}
