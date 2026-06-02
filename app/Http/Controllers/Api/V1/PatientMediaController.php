<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Doctor;
use App\Models\Patient;
use App\Enums\Medical\DoctorSpecialization;
use App\Traits\ApiResponses;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class PatientMediaController extends Controller
{
    use ApiResponses;

    /**
     * GET /patient/media/xrays
     */
    public function xrays(Request $request)
    {
        return $this->listForPatient($request, 'xray_images');
    }

    /**
     * GET /patient/media/medical-tests
     */
    public function medicalTests(Request $request)
    {
        return $this->listForPatient($request, 'medical_test_images');
    }

    /**
     * GET /patients/{patient}/media/xrays
     * Allows admin|doctor|secretary to view a specific patient's X-Rays
     */
    public function getPatientXrays(Patient $patient)
    {
        return $this->listForStaff($patient, 'xray_images');
    }

    /**
     * GET /patients/{patient}/media/medical-tests
     * Allows admin|doctor|secretary to view a specific patient's medical tests
     */
    public function getPatientMedicalTests(Patient $patient)
    {
        return $this->listForStaff($patient, 'medical_test_images');
    }

    /**
     * POST /doctor/media/xrays
     * Body: patient_id, image
     */
    public function uploadXray(Request $request)
    {
        /** @var \App\Models\Doctor $doctor */
        $doctor = $request->user()->doctor;

        // Ensure the authenticated doctor is explicitly an X-Ray Specialist
        if (!$doctor || $doctor->specialization !== DoctorSpecialization::RADIOLOGIST) {
            return response()->json([
                'message' => 'Unauthorized. Only X-Ray specialists can upload X-Ray images.'
            ], 403);
        }

        return $this->uploadForDoctorToPatient($request, 'xray_images');
    }

    /**
     * POST /doctor/media/medical-tests
     * Body: patient_id, image
     */
    public function uploadMedicalTests(Request $request)
    {
        /** @var \App\Models\Doctor $doctor */
        $doctor = $request->user()->doctor;

        // Ensure the authenticated doctor is explicitly a Medical Test Specialist
        if (!$doctor || $doctor->specialization !== DoctorSpecialization::PATHOLOGIST) {
            return response()->json([
                'message' => 'Unauthorized. Only Pathologists/Medical Test specialists can upload test images.'
            ], 403);
        }

        return $this->uploadForDoctorToPatient($request, 'medical_test_images');
    }

    /**
     * Helper to retrieve media for the authenticated patient
     */
    private function listForPatient(Request $request, string $collectionName)
    {
        /** @var Patient $patient */
        $patient = $request->user()->patient;

        return $this->buildMediaResponse($patient, $collectionName, 'Patient media retrieved successfully.');
    }

    /**
     * Helper to retrieve media for clinic staff viewing a patient
     */
    private function listForStaff(Patient $patient, string $collectionName)
    {
        return $this->buildMediaResponse($patient, $collectionName, 'Patient media retrieved successfully.');
    }

    /**
     * Core logic to compile the media array response
     */
    private function buildMediaResponse(Patient $patient, string $collectionName, string $message)
    {
        $media = $patient->getMedia($collectionName);

        return response()->json([
            'type' => 'patient_media_list',
            'data' => [
                'collection' => $collectionName,
                'media' => $media->map(fn (Media $m) => [
                    'id' => (string) $m->id,
                    'url' => $m->getUrl(),
                    'name' => $m->name,
                    'file_name' => $m->file_name,
                    'mime_type' => $m->mime_type,
                    'created_at' => $m->created_at?->toISOString(),
                ])->values(),
            ],
            'message' => $message,
            'status' => 200,
        ]);
    }

    /**
     * Core upload logic for doctors
     */
    private function uploadForDoctorToPatient(Request $request, string $collectionName)
    {
        $payload = $request->validate([
            'patient_id' => ['required', 'integer', 'exists:patients,id'],
            'image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        /** @var Patient $patient */
        $patient = Patient::findOrFail((int) $payload['patient_id']);

        /** @var UploadedFile $file */
        $file = $request->file('image');

        // Attach file to Patient model
        $patient
            ->addMedia($file)
            ->toMediaCollection($collectionName);

        // Return updated list
        $media = $patient->getMedia($collectionName);

        return response()->json([
            'type' => 'patient_media_upload',
            'data' => [
                'collection' => $collectionName,
                'media' => $media->map(fn (Media $m) => [
                    'id' => (string) $m->id,
                    'url' => $m->getUrl(),
                    'name' => $m->name,
                    'file_name' => $m->file_name,
                    'mime_type' => $m->mime_type,
                    'created_at' => $m->created_at?->toISOString(),
                ])->values(),
            ],
            'message' => 'Image uploaded successfully.',
            'status' => 201,
        ], 201);
    }
}