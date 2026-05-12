<?php

namespace App\DTOs\Consultation;

use App\DTOs\prescrionItem\PrescriptionItemDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class StoreConsultationDTO
{
    public function __construct(
        public readonly ?string $anamnesis,
        public readonly ?array $symptoms,
        public readonly ?string $diagnosis,
        public readonly ?string $nextVisitDate,
        public readonly Collection $medicines
    ) {}

    public static function fromRequest(Request $request): self
    {
        $medicines = collect($request->validated('medicines', []))
            ->map(fn (array $item) => PrescriptionItemDTO::fromArray($item));

        return new self(
            anamnesis: $request->validated('anamnesis'),
            symptoms: $request->validated('symptoms'),
            diagnosis: $request->validated('diagnosis'),
            nextVisitDate: $request->validated('next_visit_date'),
            medicines: $medicines,
        );
    }
}
