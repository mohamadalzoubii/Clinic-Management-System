<?php

namespace App\DTOs\Consutation;

use App\DTOs\prescrionItem\PrescriptionItemDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StoreConsultationDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $notes,
        public readonly ?string $nextVisitDate,
        public readonly Collection $medicines
    ) {}

    public static function fromRequest(Request $request): self
    {
        $medicines = collect($request->validated('medicines', []))
            ->map(fn(array $item) => PrescriptionItemDTO::fromArray($item));

        return new self(
            notes: $request->validated('notes'),
            nextVisitDate: $request->validated('next_visit_date'),
            medicines: $medicines,

        );
    }
}
