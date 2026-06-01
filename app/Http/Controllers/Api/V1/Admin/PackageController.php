<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\PackageResource;
use App\Models\Package;
use App\Services\Admin\PackageService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class PackageController extends Controller
{
    use ApiResponses;

    public function index()
    {
        return PackageResource::collection(Package::query()->latest()->paginate(10));
    }

    public function show(Package $package)
    {
        return new PackageResource($package);
    }

    public function store(Request $request, PackageService $service)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'balance_amount' => ['required', 'numeric', 'min:0'],
        ]);

        $package = $service->store($data);

        return $this->success('Package created successfully.', [
            'package' => new PackageResource($package),
        ], 201);
    }

    public function update(Request $request, Package $package, PackageService $service)
    {
        $data = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'balance_amount' => ['sometimes', 'numeric', 'min:0'],
        ]);

        $package = $service->update($package, $data);

        return $this->ok('Package updated successfully.', [
            'package' => new PackageResource($package),
        ]);
    }

    public function destroy(Package $package, PackageService $service)
    {
        $service->delete($package);

        return $this->ok('Package deleted successfully.');
    }
}