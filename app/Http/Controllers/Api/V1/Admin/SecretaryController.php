<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\DTOs\Secretary\StoreSecretaryData;
use App\DTOs\Secretary\UpdateSecretaryData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSecretaryRequest;
use App\Http\Requests\Admin\UpdateSecretaryRequest;
use App\Http\Resources\Api\V1\Admin\SecretaryResource;
use App\Models\Secretary;
use App\Services\Admin\SecretaryService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class SecretaryController extends Controller
{
    use ApiResponses;

    public function __construct(private readonly SecretaryService $service) {}

    public function index(Request $request)
    {
        $search = $request->query('search');

        $query = Secretary::with('user')->latest();

        if ($search) {
            $query->whereHas('user', function ($userQuery) use ($search) {
                $userQuery
                    ->where('first_name', 'like', '%'.$search.'%')
                    ->orWhere('last_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('phone', 'like', '%'.$search.'%');
            });
        }

        return SecretaryResource::collection($query->paginate(10));
    }

    public function show(Secretary $secretary)
    {
        return new SecretaryResource($secretary->loadMissing('user'));
    }

    public function store(StoreSecretaryRequest $request)
    {
        $secretary = $this->service->store(StoreSecretaryData::formRequest($request));

        return $this->success('Secretary created successfully.', [
            'secretary' => new SecretaryResource($secretary->loadMissing('user')),
        ], 201);
    }

    public function update(UpdateSecretaryRequest $request, Secretary $secretary)
    {
        $secretary = $this->service->update($secretary, UpdateSecretaryData::formRequest($request));

        return $this->ok('Secretary updated successfully.', [
            'secretary' => new SecretaryResource($secretary),
        ]);
    }

    public function destroy(Secretary $secretary)
    {
        $this->service->delete($secretary);

        return $this->ok('Secretary deleted successfully.');
    }
}
