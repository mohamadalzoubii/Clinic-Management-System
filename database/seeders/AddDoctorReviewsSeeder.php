<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Models\Patient;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddDoctorReviewsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->addDoctorImages();
        $this->addDoctorReviews(); 
    }

    private function addDoctorImages(): void
    {
        $doctors = Doctor::all();
        $imagePath = base_path('Assets/doctor images');

        $doctors->each(function (Doctor $doctor, int $index) use ($imagePath) {
            $imageNumber = ($index % 9) + 1;
            $imageFile = $imagePath."/doctor".$imageNumber.".png";

            if (file_exists($imageFile)) {
                $doctor->addMedia($imageFile)
                    ->preservingOriginal()
                    ->toMediaCollection('doctor_photo');
            }
        });

        $this->command->info('Doctor images added successfully.');
    }

    private function addDoctorReviews(): void
    {
        $doctors = Doctor::all();
        $patients = Patient::all();

        if ($doctors->isEmpty() || $patients->isEmpty()) {
            $this->command->warn('No doctors or patients found.');
            return;
        }

        $comments = [
            5 => ['Excellent doctor!', 'Best ever!', 'Great!', 'Outstanding!', 'Fantastic!', 'Highly recommended!'],
            4 => ['Good experience.', 'Helpful!', 'Satisfied.', 'Good doctor.', 'Nice!'],
            3 => ['Average.', 'Ok.', 'Fair.'],
        ];

        $createdCount = 0;
        $skippedCount = 0;

        $doctors->each(function (Doctor $doctor) use ($patients, $comments, &$createdCount, &$skippedCount) {
            $reviewCount = rand(8, 15);
            $randomPatients = $patients->random($reviewCount);

            $randomPatients->each(function (Patient $patient) use ($doctor, $comments, &$createdCount, &$skippedCount) {
                try {
                    $rating = $this->weightedRandomRating();
                    $comment = $comments[$rating][array_rand($comments[$rating])];

                    DoctorReview::create([
                        'doctor_id' => $doctor->id,
                        'patient_id' => $patient->id,
                        'rating' => $rating,
                        'comment' => $comment,
                    ]);
                    $createdCount++;
                } catch (\Exception $e) {
                    $skippedCount++;
                }
            });
        });

        $this->command->info("Doctor reviews added: $createdCount, skipped: $skippedCount.");
    }

    private function weightedRandomRating(): int
    {
        $weights = [5=>50,4=>30,3=>15,2=>4,1=>1];
        $roll = rand(1,100); $c = 0;
        foreach($weights as $r=>$w){ $c+=$w; if($roll<=$c)return $r; }
        return 5;
    }
}

