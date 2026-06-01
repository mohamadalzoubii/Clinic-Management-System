<?php

namespace App\Services\Admin;

use App\Models\Package;

class PackageService
{
    public function store(array $data): Package
    {
        return Package::create(array_merge($data, ['price' => 0]));
    }

    public function update(Package $package, array $data): Package
    {
        $package->update(array_merge($data, ['price' => 0]));

        return $package->fresh();
    }

    public function delete(Package $package): void
    {
        $package->delete();
    }
}