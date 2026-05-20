<?php

namespace App\Http\Controllers;

use App\Actions\Medical\Patient\PatientProfileAction;
use App\DTOs\Patient\PatientProfileData;
use App\Http\Requests\Api\V1\Patient\PatientProfileRequest;
use App\Http\Requests\Api\V1\Patient\PatientProfileUpdateRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Http\Resources\PatientResource;
use App\Models\Patient;
use App\Traits\ApiResponses;

class PatientController extends Controller
{
    use ApiResponses;

    public function index()
    {
        return PatientResource::collection(Patient::with('user')->get());
    }

    public function show(Patient $patient)
    {
        return new PatientResource($patient->load('user'));
    }

    public function completeProftile(PatientProfileRequest $request, PatientProfileAction $action)
    {
        $dto = PatientProfileData::fromRequest($request);

        $user = $action->execute($request->user(), $dto);

        $user->load('patient');

        return $this->ok(
            'Your profile is completed successfully',
            [
                'user' => (new UserResource($user))->resolve(),
            ]
        );
    }

    public function updateProfile(PatientProfileUpdateRequest $request, PatientProfileAction $action)
    {
        $dto = PatientProfileData::fromArray($request->validated());
        $user = auth()->user();
        $updatePatinet = $action->execute($user, $dto);

        return $this->ok('your profile is updated successfully',
            [
                'user' => new UserResource($updatePatinet),
            ]
        );
    }
}
