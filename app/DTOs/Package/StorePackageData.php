<?php

namespace App\DTOs\Package;

use App\Http\Requests\Admin\StorePackageRequest;
use Illuminate\Http\Request;

readonly class StorePackageData
{
    public function __construct(
        public string $name,
        public float $balanceAmount,
    ) {}

    public static function formRequest(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            name: $validated['name'],
            balanceAmount: (float) $validated['balance_amount'],
        );
    }
}
