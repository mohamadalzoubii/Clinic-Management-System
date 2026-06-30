<?php

namespace Database\Seeders;

use App\Enums\Medical\AppointmentStatus;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Patient;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AddConsultationsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->addConsultations();
    }

    private function addConsultations(): void
    {
        $faker = Factory::create();
        $patients = Patient::with("user")->get();
        $doctors = Doctor::all();

        if ($patients->isEmpty() || $doctors->isEmpty()) {
            $this->command->warn("No patients or doctors found.");
            return;
        }

        $createdCount = 0;
        $skippedCount = 0;

        $patients->each(function (Patient $patient) use ($doctors, $faker, &$createdCount, &$skippedCount) {
            $consultationCount = rand(1, 3);

            for ($i = 0; $i < $consultationCount; $i++) {
                try {
                    $doctor = $doctors->random();
                    $daysAgo = rand(1, 90);
                    $consultationDate = Carbon::now()->subDays($daysAgo);

                    $appointment = Appointment::create([
                        "patient_id" => $patient->id,
                        "doctor_id" => $doctor->id,
                        "appointment_date" => $consultationDate->format("Y-m-d"),
                        "start_time" => $faker->time("H:i:s"),
                        "end_time" => $faker->time("H:i:s"),
                        "status" => AppointmentStatus::COMPLETED->value,
                        "reason" => $faker->sentence(),
                    ]);

                    $consultation = Consultation::create([
                        "appointment_id" => $appointment->id,
                        "doctor_id" => $doctor->id,
                        "patient_id" => $patient->id,
                        "anamnesis" => $this->generateAnamnesis($faker),
                        "symptoms" => $this->generateSymptoms($faker),
                        "diagnosis" => $this->generateDiagnosis($faker),
                        "next_visit_date" => $consultationDate->addDays(rand(7, 30))->format("Y-m-d"),
                    ]);

                    $consultation->prescriptionItems()->createMany($this->generatePrescriptions());

                    Invoice::create([
                        "appointment_id" => $appointment->id,
                        "user_id" => $patient->user_id,
                        "amount" => $doctor->session_price ?? 50,
                        "invoice_number" => "INV-" . strtoupper($faker->bothify("????-####")),
                        "entry_type" => "appointment_payment",
                        "status" => "paid",
                        "paid_at" => $consultationDate,
                    ]);

                    $createdCount++;
                } catch (\Exception $e) {
                    $skippedCount++;
                }
            }
        });

        $this->command->info("Consultations added: $createdCount, skipped: $skippedCount.");
    }

    private function generateAnamnesis($faker): string
    {
        $templates = [
            "Patient presented with persistent headaches for the past week. Reports fatigue and mild fever. Vitals are stable. Advised rest and plenty of fluids. Follow up in one week if symptoms persist.",
            "Complaining of chest congestion and persistent cough for several days. Breathing is clear on auscultation. Prescribed cough syrup and rest. Return if fever develops.",
            "Experiencing lower back pain after lifting heavy objects. Physical examination shows muscle strain. Recommended pain medication and hot compress therapy.",
            "Reports dizziness and occasional nausea. Blood pressure is within normal range. Ordered blood work to check for anemia. Given anti-nausea medication.",
            "Chronic joint pain flaring up. Patient has history of arthritis. Current medications reviewed. Added new anti-inflammatory medication.",
            "Annual wellness check. All vitals normal. Patient reports occasional insomnia. Discussed sleep hygiene and recommended lifestyle changes."
        ];
        return $templates[array_rand($templates)];
    }

    private function generateSymptoms($faker): array
    {
        $symptomsList = [
            ["headache", "fatigue", "fever"],
            ["cough", "congestion", "sore throat"],
            ["back pain", "muscle ache", "stiffness"],
            ["dizziness", "nausea", "weakness"],
            ["joint pain", "swelling", "stiffness"],
            ["insomnia", "anxiety", "stress"]
        ];
        return $symptomsList[array_rand($symptomsList)];
    }

    private function generateDiagnosis($faker): string
    {
        $diagnoses = [
            "Upper Respiratory Infection",
            "Muscle Strain - Lower Back",
            "Tension Headache",
            "Mild Dehydration",
            "Osteoarthritis - Early Stage",
            "Insomnia - Secondary",
            "Annual Wellness Check - Healthy",
            "Viral Infection - Recovering"
        ];
        return $diagnoses[array_rand($diagnoses)];
    }

    private function generatePrescriptions(): array
    {
        $rxOptions = [
            [
                ["medicine_name" => "Acetaminophen", "category" => "Pain Reliever", "dosage" => "500mg", "form_and_quantity" => "20 tablets", "frequency" => "Every 6 hours as needed", "duration" => "5 days", "special_instructions" => "Take with food", "storage_instructions" => "Room temperature", "side_effects" => "May cause drowsiness", "allergy_warnings" => null],
                ["medicine_name" => "Vitamin C", "category" => "Supplement", "dosage" => "1000mg", "form_and_quantity" => "30 tablets", "frequency" => "Once daily", "duration" => "30 days", "special_instructions" => "Take in morning", "storage_instructions" => "Cool dry place", "side_effects" => null, "allergy_warnings" => null]
            ],
            [
                ["medicine_name" => "Amoxicillin", "category" => "Antibiotic", "dosage" => "500mg", "form_and_quantity" => "21 capsules", "frequency" => "Three times daily", "duration" => "7 days", "special_instructions" => "Complete full course", "storage_instructions" => "Refrigerate", "side_effects" => "Nausea", "allergy_warnings" => "Penicillin allergy"],
                ["medicine_name" => "Ibuprofen", "category" => "NSAID", "dosage" => "400mg", "form_and_quantity" => "15 tablets", "frequency" => "Twice daily with food", "duration" => "5 days", "special_instructions" => "Take after meals", "storage_instructions" => "Room temperature", "side_effects" => "Stomach upset", "allergy_warnings" => null]
            ],
            [
                ["medicine_name" => "Cetirizine", "category" => "Antihistamine", "dosage" => "10mg", "form_and_quantity" => "10 tablets", "frequency" => "Once daily", "duration" => "10 days", "special_instructions" => "May cause drowsiness", "storage_instructions" => "Room temperature", "side_effects" => "Drowsiness", "allergy_warnings" => null]
            ]
        ];
        return $rxOptions[array_rand($rxOptions)];
    }
}

