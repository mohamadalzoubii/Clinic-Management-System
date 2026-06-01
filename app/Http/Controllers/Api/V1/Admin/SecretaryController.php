<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SecretaryResource;
use App\Models\Secretary;
use App\Services\Admin\SecretaryService;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;

class SecretaryController extends Controller
{
    use ApiResponses;

    public function index()
    {
        $search = request()->query('search');

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

    public function store(Request $request, SecretaryService $service)
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:8'],
            'work_days' => ['nullable', 'string', 'max:255'],
            'monthly_salary' => ['nullable', 'numeric', 'min:0'],
        ]);

        $secretary = $service->store($data);

        return $this->success('Secretary created successfully.', [
            'secretary' => new SecretaryResource($secretary->loadMissing('user')),
        ], 201);
    }

    public function update(Request $request, Secretary $secretary, SecretaryService $service)
    {
        $data = $request->validate([
            'first_name' => ['sometimes', 'string', 'max:255'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$secretary->user_id],
            'phone' => ['nullable', 'sometimes', 'string', 'max:20', 'unique:users,phone,'.$secretary->user_id],
            'password' => ['sometimes', 'string', 'min:8'],
            'work_days' => ['nullable', 'sometimes', 'string', 'max:255'],
            'monthly_salary' => ['nullable', 'sometimes', 'numeric', 'min:0'],
        ]);

        $secretary = $service->update($secretary, $data);

        return $this->ok('Secretary updated successfully.', [
            'secretary' => new SecretaryResource($secretary),
        ]);
    }

    public function destroy(Secretary $secretary, SecretaryService $service)
    {
        $service->delete($secretary);

        return $this->ok('Secretary deleted successfully.');
    }
}