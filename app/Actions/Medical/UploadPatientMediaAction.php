<?php

namespace App\Actions\Medical;

use App\DTOs\UploadPatientMediaData;
use App\Models\Patient;
use Illuminate\Support\Collection;

class UploadPatientMediaAction
{
    public function execute(UploadPatientMediaData $data, string $collectionName): Collection
    {
        $patient = Patient::findOrFail($data->patientId);

        $patient
            ->addMedia($data->image)
            ->toMediaCollection($collectionName);

        return $patient->getMedia($collectionName);
    }
}
