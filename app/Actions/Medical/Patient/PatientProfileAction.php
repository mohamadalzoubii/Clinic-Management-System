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

            $user->update([
                'first_name' => $dto->first_name,
                'last_name' => $dto->last_name,
                'phone' => $dto->phone_number,
            ]);

            $user->patient()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'date_of_birth' => $dto->date_of_birth,
                    'emergency_contact_name' => $dto->emergency_contact_name,
                    'emergency_contact_phone' => $dto->emergency_contact_phone,
                    'allergies' => $dto->allergies,
                    'chronic_diseases' => $dto->chronic_diseases,
                    'weight' => $dto->weight,
                    'height' => $dto->height,
                    'blood_type' => $dto->blood_type,
                    'is_smoker' => $dto->is_smoker,
                    'drinks_alcohol' => $dto->drinks_alcohol,

                ]
            );

            return $user->refresh();
        });
    }
}
