<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\Package\StorePackageData;
use App\DTOs\Package\UpdatePackageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePackageRequest;
use App\Http\Requests\Admin\UpdatePackageRequest;
use App\Http\Resources\Api\V1\Admin\PackageResource;
use App\Models\Package;
use App\Services\Admin\PackageService;
use App\Traits\ApiResponses;

class PackageController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly PackageService $service) {}

    public function index()
    {
        return PackageResource::collection(Package::query()->latest()->paginate(10));
    }

    public function show(Package $package)
    {
        return new PackageResource($package);
    }

    public function store(StorePackageRequest $request)
    {
        $package = $this->service->store(StorePackageData::formRequest($request));

        return $this->success('Package created successfully.', [
            'package' => new PackageResource($package),
        ], 201);
    }

    public function update(UpdatePackageRequest $request, Package $package)
    {
        $package = $this->service->update($package, UpdatePackageData::formRequest($request));

        return $this->ok('Package updated successfully.', [
            'package' => new PackageResource($package),
        ]);
    }

    public function destroy(Package $package)
    {
        $this->service->delete($package);

        return $this->ok('Package deleted successfully.');
    }
}
