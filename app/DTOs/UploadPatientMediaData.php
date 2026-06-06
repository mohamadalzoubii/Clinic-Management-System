<?php

namespace App\DTOs;

use App\Http\Requests\UploadPatientMediaRequest;
use Illuminate\Http\UploadedFile;

class UploadPatientMediaData
{
    public function __construct(
        public int $patientId,
        public UploadedFile $image
    ) {}

    public static function formRequest(UploadPatientMediaRequest $request): self
    {
        return new self(
            patientId: (int) $request->validated('patient_id'),
            image: $request->file('image')
        );
    }
}
