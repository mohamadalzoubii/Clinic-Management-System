<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Patient;
use App\Enums\Medical\DoctorSpecialization;
use App\Traits\ApiResponses;
use App\Http\Requests\UploadPatientMediaRequest;
use App\DTOs\UploadPatientMediaData;
use App\Actions\Medical\UploadPatientMediaAction;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Collection;

class PatientMediaController extends Controller
{
    use ApiResponses;

    public function xrays(Request $request)
    {
        return $this->listForPatient($request, 'xray_images');
    }

    public function medicalTests(Request $request)
    {
        return $this->listForPatient($request, 'medical_test_images');
    }

    public function getPatientXrays(Patient $patient)
    {
        return $this->listForStaff($patient, 'xray_images');
    }

    public function getPatientMedicalTests(Patient $patient)
    {
        return $this->listForStaff($patient, 'medical_test_images');
    }

    public function uploadXray(UploadPatientMediaRequest $request, UploadPatientMediaAction $action)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor || $doctor->specialization !== DoctorSpecialization::RADIOLOGIST) {
            return $this->error('Unauthorized. Only X-Ray specialists can upload X-Ray images.', 403);
        }

        $dto = UploadPatientMediaData::formRequest($request);
        $media = $action->execute($dto, 'xray_images');

        return $this->success($this->transformMedia($media), 'X-Ray image uploaded successfully.', 201);
    }

    public function uploadMedicalTests(UploadPatientMediaRequest $request, UploadPatientMediaAction $action)
    {
        $doctor = $request->user()->doctor;

        if (!$doctor || $doctor->specialization !== DoctorSpecialization::PATHOLOGIST) {
            return $this->error('Unauthorized. Only Pathologists/Medical Test specialists can upload test images.', 403);
        }

        $dto = UploadPatientMediaData::formRequest($request);
        $media = $action->execute($dto, 'medical_test_images');

        return $this->success($this->transformMedia($media), 'Medical test image uploaded successfully.', 201);
    }

    private function listForPatient(Request $request, string $collectionName)
    {
        $patient = $request->user()->patient;

        return $this->buildMediaResponse($patient, $collectionName, 'Patient media retrieved successfully.');
    }

    private function listForStaff(Patient $patient, string $collectionName)
    {
        return $this->buildMediaResponse($patient, $collectionName, 'Patient media retrieved successfully.');
    }

    private function buildMediaResponse(Patient $patient, string $collectionName, string $message)
    {
        $media = $patient->getMedia($collectionName);

        return $this->success($this->transformMedia($media), $message);
    }

    private function transformMedia(Collection $media): array
    {
        return $media->map(fn (Media $m) => [
            'id' => (string) $m->id,
            'url' => $m->getUrl(),
            'name' => $m->name,
            'file_name' => $m->file_name,
            'mime_type' => $m->mime_type,
            'created_at' => $m->created_at?->toISOString(),
        ])->values()->all();
    }
}
