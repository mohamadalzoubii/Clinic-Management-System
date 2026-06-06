<?php

namespace App\Services\Admin;

use App\DTOs\Package\StorePackageData;
use App\DTOs\Package\UpdatePackageData;
use App\Models\Package;

class PackageService
{
    public function store(StorePackageData $dto): Package
    {
        return Package::create([
            'name' => $dto->name,
            'balance_amount' => $dto->balanceAmount,
            'price' => 0,
        ]);
    }

    public function update(Package $package, UpdatePackageData $dto): Package
    {
        if (! $dto->hasChanges()) {
            return $package->fresh();
        }

        $payload = array_filter([
            'name' => $dto->name,
            'balance_amount' => $dto->balanceAmount,
        ], static fn ($value) => $value !== null);

        $package->update($payload);

        return $package->fresh();
    }

    public function delete(Package $package): void
    {
        $package->delete();
    }
}
