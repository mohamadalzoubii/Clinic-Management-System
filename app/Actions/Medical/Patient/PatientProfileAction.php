<?php

namespace App\Actions\Medical\Patient;

use App\DTOs\Patient\PatientProfileData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PatientProfileAction
{
    public function execute(User $user, PatientProfileData $dto)
    {
        return DB::transaction(function () use ($dto, $user) {
            $user->patient()->update([
                'emergency_contact_name' => $dto->emergency_contact_name,
                'emergency_contact_phone' => $dto->emergency_contact_phone,
                'allergies' => $dto->allergies,
                'chronic_diseases' => $dto->chronic_diseases,
                'weight' => $dto->weight,
                'height' => $dto->height,
                'gender' => $dto->gender,
                'blood_type' => $dto->blood_type,
                'date_of_birth' => $dto->date_of_birth,
            ]);
            return $user;
        });
    }
}
