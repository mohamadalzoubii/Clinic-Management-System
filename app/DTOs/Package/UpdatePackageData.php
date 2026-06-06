<?php

namespace App\DTOs\Package;

use App\Http\Requests\Admin\UpdatePackageRequest;
use Illuminate\Http\Request;

readonly class UpdatePackageData
{
    public function __construct(
        public ?string $name,
        public ?float $balanceAmount,
    ) {}

    public static function formRequest(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'] ?? null,
            balanceAmount: isset($validated['balance_amount']) ? (float) $validated['balance_amount'] : null,
        );
    }

    public function hasChanges(): bool
    {
        return $this->name !== null || $this->balanceAmount !== null;
    }
}
