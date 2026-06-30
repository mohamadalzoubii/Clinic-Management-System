<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\DoctorReview;
use App\Models\Patient;
use Faker\Factory;
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
        $faker = Factory::create();

        if ($doctors->isEmpty() || $patients->isEmpty()) {
            $this->command->warn('No doctors or patients found.');
            return;
        }

        $createdCount = 0;
        $skippedCount = 0;

        $doctors->each(function (Doctor $doctor) use ($patients, $faker, &$createdCount, &$skippedCount) {
            $reviewCount = rand(1, 5);
            $randomPatients = $patients->random(min($reviewCount, $patients->count()));

            $randomPatients->each(function (Patient $patient) use ($doctor, $faker, &$createdCount, &$skippedCount) {
                try {
                    $rating = $this->weightedRandomRating();
                    $comment = $this->generateComment($faker, $rating);

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

    private function generateComment($faker, int $rating): string
    {
        $excellent = [
            "Dr. exceeded all my expectations. Very thorough and caring. Explained everything clearly and made me feel comfortable throughout the entire consultation. Would highly recommend to anyone seeking quality medical care.",
            "Outstanding physician! The best doctor I have ever seen. Very professional, patient, and attentive. Took the time to listen to all my concerns and provided clear explanations. Truly exceptional care.",
            "Absolutely fantastic experience. The doctor was very knowledgeable and took great care of me. The follow-up was excellent and I felt genuinely cared for. This is exactly what every patient deserves.",
            "Best medical experience I have had in years. The doctor was compassionate, skilled, and very thorough. No rushed appointments - actually listened and addressed all my issues. Highly recommend!",
            "Exceptional doctor with wonderful bedside manner. Very experienced and professional. Made a difficult diagnosis much easier to handle with their supportive approach. Truly outstanding care."
        ];
        
        $good = [
            "Very good doctor, spent adequate time explaining my condition. Professional and knowledgeable. Would definitely return for future visits. Overall satisfied with the care received.",
            "Good experience overall. The doctor was attentive and explained the treatment options clearly. Made me feel at ease during the consultation. Recommend for general health issues.",
            "Satisfied with my visit. The physician was friendly and thorough. Got my prescription and follow-up instructions without any confusion. Would see again.",
            "Decent doctor, could spend a bit more time but addressed all my concerns. Professional manner and provided clear instructions. Good for routine checkups.",
            "Reasonable visit. The doctor answered questions and provided helpful advice. Nothing exceptional but no complaints either. Would return if needed."
        ];
        
        $average = [
            "Average experience. Got the basic treatment needed. Nothing special but nothing wrong either. Standard medical visit.",
            "Fair - the doctor did what was needed. Might try a different doctor next time for better rapport.",
            "OK doctor for simple issues. Not bad but not exceptional either. Gets the job done."
        ];

        $templates = [
            5 => $excellent,
            4 => $good,
            3 => $average
        ];

        return $templates[$rating][array_rand($templates[$rating])];
    }

    private function weightedRandomRating(): int
    {
        $weights = [5=>50,4=>30,3=>15,2=>4,1=>1];
        $roll = rand(1,100); $c = 0;
        foreach($weights as $r=>$w){ $c+=$w; if($roll<=$c)return $r; }
        return 5;
    }
}

