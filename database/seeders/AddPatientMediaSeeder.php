<?php

namespace Database\Seeders;

use App\Models\Patient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Faker\Factory;
use Illuminate\Http\UploadedFile;

class AddPatientMediaSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $patients = Patient::query()->get();

        $xrayDir = base_path('Assets/xrays');
        $medicalDir = base_path('Assets/medical tests');

        $xrayFiles = $this->listImageFiles($xrayDir);
        $medicalFiles = $this->listImageFiles($medicalDir);

        if (empty($patients) || empty($xrayFiles) || empty($medicalFiles)) {
            return;
        }

        foreach ($patients as $patient) {
            // Add 1 xray + 1 medical test per patient.
            $xrayFile = $xrayFiles[array_rand($xrayFiles)];
            $medicalFile = $medicalFiles[array_rand($medicalFiles)];

            $this->attachPatientMedia($patient, $xrayFile, 'xray_images');
            $this->attachPatientMedia($patient, $medicalFile, 'medical_test_images');
        }
    }

    private function attachPatientMedia(Patient $patient, string $absolutePath, string $collection): void
    {
        $filename = pathinfo($absolutePath, PATHINFO_BASENAME);

        // Prevent duplicates in case the seeder is re-run.
        $existingNames = $patient->getMedia($collection)->pluck('file_name')->all();
        if (in_array($filename, $existingNames, true)) {
            return;
        }

        // Pass the absolute path directly and chain preservingOriginal()
        $patient->addMedia($absolutePath)
                ->preservingOriginal() // <-- This keeps your original file in the Assets folder
                ->toMediaCollection($collection);
    }

    private function listImageFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $files = array_values(array_filter(scandir($dir), function ($f) use ($dir) {
            $path = $dir . DIRECTORY_SEPARATOR . $f;
            return is_file($path) && preg_match('/\.(png|jpg|jpeg|gif|webp)$/i', $f);
        }));

        return array_map(fn ($f) => $dir . DIRECTORY_SEPARATOR . $f, $files);
    }
}

