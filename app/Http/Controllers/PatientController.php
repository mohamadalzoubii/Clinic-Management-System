<?php

namespace App\Http\Controllers;

use App\Actions\Medical\Patient\PatientProfileAction;
use App\DTOs\Patient\PatientProfileData;
use App\Http\Requests\Api\V1\Patient\PatientProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Traits\ApiResponses;

class PatientController extends Controller
{
    use ApiResponses;

    public function CompleteProftile(PatientProfileRequest $request, PatientProfileAction $action)
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
}
