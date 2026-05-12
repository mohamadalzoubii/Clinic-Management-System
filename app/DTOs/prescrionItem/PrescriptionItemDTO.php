<?php

namespace App\DTOs\prescrionItem;

class PrescriptionItemDTO
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $category,
        public readonly string $dosage,
        public readonly ?string $formAndQuantity,
        public readonly string $frequency,
        public readonly string $duration,
        public readonly ?string $specialInstructions,
        public readonly ?string $storageInstructions,
        public readonly ?string $sideEffects,
        public readonly ?string $allergyWarnings,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'],
            category: $data['category'] ?? null,
            dosage: $data['dosage'],
            formAndQuantity: $data['form_and_quantity'] ?? null,
            frequency: $data['frequency'],
            duration: $data['duration'],
            specialInstructions: $data['special_instructions'] ?? null,
            storageInstructions: $data['storage_instructions'] ?? null,
            sideEffects: $data['side_effects'] ?? null,
            allergyWarnings: $data['allergy_warnings'] ?? null,
        );
    }
}
