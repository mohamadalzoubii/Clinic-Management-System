<?php

namespace Database\Seeders;

use App\Enums\Medical\AppointmentStatus;
use App\Enums\Medical\VacationStatus;
use App\Enums\Medical\DoctorSpecialization;
use App\Enums\RoleEnum;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Review;
use App\Models\Vacation;
use App\Models\User;
use App\Services\Medical\DoctorScheduleVersionService;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Spatie\Permission\PermissionRegistrar;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Reset Spatie Cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Create Roles
        $this->call([RoleSeeder::class]);
        $this->call([AdminAccountSeeder::class]);
        $this->call([SecretaryAccountSeeder::class]);

        $doctorUser = User::factory()->create([
            'first_name' => 'doctor',
            'last_name' => 'Test',
            'email' => 'doctor@test.com',
            'password' => Hash::make('password'),
            'user_status' => 'approved',
        ]);

        $doctorUser->assignRole(RoleEnum::DOCTOR->value);

        $doctor = Doctor::factory()->create([
            'user_id' => $doctorUser->id,
            'session_price' => 50.00,
        ]);

        DoctorSchedule::factory()->create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 'monday',
            'start_time' => '09:00',
            'end_time' => '12:00',
        ]);

        $patientUser = User::factory()->create([
            'first_name' => 'Patient',
            'last_name' => 'Test',
            'email' => 'patient@test.com',
            'password' => Hash::make('password'),
            'user_status' => 'approved',
        ]);

        $patientUser->assignRole(RoleEnum::PATIENT->value);

        $patient = Patient::factory()->create(['user_id' => $patientUser->id]);

        $faker = Factory::create();

        // 3. Create Packages
        $packages = [
            ['name' => 'Basic Wellness', 'price' => 0, 'balance_amount' => 60],
            ['name' => 'Premium Care', 'price' => 0, 'balance_amount' => 125],
            ['name' => 'VIP Family', 'price' => 0, 'balance_amount' => 260],
        ];
        foreach ($packages as $pkg) {
            Package::firstOrCreate(['name' => $pkg['name']], $pkg);
        }

        // 4. Create 20 Patients (With random wallet balances)
        $patients = User::factory(20)
            ->has(Patient::factory(), 'patient')
            ->create([
                'user_status' => 'approved',
                'password' => Hash::make('password'),
                'wallet_balance' => rand(50, 300),
            ]);

        foreach ($patients as $user) {
            $user->assignRole(RoleEnum::PATIENT->value);
        }

        // 5. Create 10 General Doctors (With Schedules)
        $doctors = User::factory(10)
            ->has(Doctor::factory()->has(DoctorSchedule::factory()->count(4), 'schedules'), 'doctor')
            ->create([
                'user_status' => 'approved',
                'password' => Hash::make('password'),
            ]);

        foreach ($doctors as $user) {
            $user->assignRole(RoleEnum::DOCTOR->value);
        }

        // 5b. Create 1 Dedicated X-Ray Specialist (Radiologist)
        $radiologistUser = User::factory()->create([
            'first_name' => 'Radiologist',
            'last_name' => 'Specialist',
            'email' => 'radiologist@test.com',
            'password' => Hash::make('password'),
            'user_status' => 'approved',
        ]);
        $radiologistUser->assignRole(RoleEnum::DOCTOR->value);
        $radiologist = Doctor::factory()->create([
            'user_id' => $radiologistUser->id,
            'specialization' => DoctorSpecialization::RADIOLOGIST->value,
            'session_price' => 65.00,
        ]);
        DoctorSchedule::factory()->count(4)->create(['doctor_id' => $radiologist->id]);

        // 5c. Create 1 Dedicated Medical Test Specialist (Pathologist)
        $pathologistUser = User::factory()->create([
            'first_name' => 'Pathologist',
            'last_name' => 'Specialist',
            'email' => 'pathologist@test.com',
            'password' => Hash::make('password'),
            'user_status' => 'approved',
        ]);
        $pathologistUser->assignRole(RoleEnum::DOCTOR->value);
        $pathologist = Doctor::factory()->create([
            'user_id' => $pathologistUser->id,
            'specialization' => DoctorSpecialization::PATHOLOGIST->value,
            'session_price' => 55.00,
        ]);
        DoctorSchedule::factory()->count(4)->create(['doctor_id' => $pathologist->id]);

        $vacationDoctors = $doctors->take(2);

        foreach ($vacationDoctors as $index => $doctorUser) {
            $this->seedDoctorVacation(
                $doctorUser->doctor,
                Carbon::today()->subWeek(),
                Carbon::today()->addMonths(2),
                $index === 0 ? 'admin' : 'secretary'
            );
        }

        $adminUserId = User::query()->where('email', 'admin@test.com')->value('id');

        $this->seedVersionedAgendaForDoctor($doctor, $patients, $adminUserId);

        foreach ($doctors as $doctorUser) {
            $this->seedVersionedAgendaForDoctor($doctorUser->doctor, $patients, $adminUserId);
        }

        // Seed versioned agendas for the diagnostic specialists
        $this->seedVersionedAgendaForDoctor($radiologist, $patients, $adminUserId);
        $this->seedVersionedAgendaForDoctor($pathologist, $patients, $adminUserId);

        // 6. Generate "The Living System" (Appointments, Consultations, Prescriptions, Invoices, Reviews)
        $appointmentStatuses = AppointmentStatus::cases();

        // Merge standard doctors with newly created specialists so they all receive simulation history
        $allDoctorUsers = $doctors->concat([$radiologistUser, $pathologistUser]);

        foreach ($allDoctorUsers as $docUser) {
            $doctor = $docUser->doctor;

            // Create 5 to 15 appointments per doctor
            $numAppointments = rand(5, 15);

            for ($i = 0; $i < $numAppointments; $i++) {
                $randomPatient = $patients->random()->patient;
                $status = $faker->randomElement($appointmentStatuses)->value;

                $isPast = in_array($status, [AppointmentStatus::COMPLETED->value, AppointmentStatus::CANCELLED->value]);
                $date = $isPast ? Carbon::now()->subDays(rand(1, 30)) : Carbon::now()->addDays(rand(1, 14));

                // A. Create Appointment
                $appointment = Appointment::create([
                    'patient_id' => $randomPatient->id,
                    'doctor_id' => $doctor->id,
                    'appointment_date' => $date->format('Y-m-d'),
                    'start_time' => '10:00:00',
                    'end_time' => '10:30:00',
                    'status' => $status,
                    'reason' => $faker->sentence(),
                ]);

                // B. If Completed -> Generate Consultation, Rx, and Invoice
                if ($status === AppointmentStatus::COMPLETED->value) {

                    // 1. Consultation
                    $consultation = Consultation::create([
                        'appointment_id' => $appointment->id,
                        'doctor_id' => $doctor->id,
                        'patient_id' => $randomPatient->id,
                        'anamnesis' => 'Patient presented with ' . $faker->word() . ' pain. Vitals are stable. Advised rest and hydration.',
                        'symptoms' => [$faker->word(), $faker->word()],
                        'diagnosis' => 'General Fatigue',
                        'next_visit_date' => $date->copy()->addDays(7)->format('Y-m-d'),
                    ]);

                    // 2. Prescription Items
                    $consultation->prescriptionItems()->createMany([
                        [
                            'medicine_name' => 'Amoxicillin',
                            'category' => 'Antibiotic',
                            'dosage' => '500mg',
                            'form_and_quantity' => '14 capsules',
                            'frequency' => 'Twice daily',
                            'duration' => '7 days',
                            'special_instructions' => 'Take after meals',
                            'storage_instructions' => 'Store in a cool dry place',
                            'side_effects' => 'Nausea or mild stomach upset',
                            'allergy_warnings' => 'Avoid if allergic to penicillin',
                        ],
                        [
                            'medicine_name' => 'Ibuprofen',
                            'category' => 'NSAID (Painkiller)',
                            'dosage' => '400mg',
                            'form_and_quantity' => '10 tablets',
                            'frequency' => 'As needed for pain',
                            'duration' => '3 days',
                            'special_instructions' => 'Do not exceed 3 tablets in 24 hours',
                            'storage_instructions' => null,
                            'side_effects' => null,
                            'allergy_warnings' => null,
                        ],
                    ]);

                    // 3. Invoice
                    Invoice::create([
                        'appointment_id' => $appointment->id,
                        'user_id' => $randomPatient->user_id,
                        'amount' => rand(30, 100),
                        'invoice_number' => 'INV-' . strtoupper($faker->bothify('????-####')),
                        'status' => 'paid',
                        'paid_at' => $date->copy()->addHours(1),
                    ]);
                }
            }
        }
        $this->call([AddDoctorReviewsSeeder::class]);
        $this->call([UpdateProfileFieldsSeeder::class]);
    }

    private function seedVersionedAgendaForDoctor(Doctor $doctor, Collection $patients, ?int $adminUserId = null): void
    {
        $faker = Factory::create();
        $versionService = app(DoctorScheduleVersionService::class);

        $versionService->createVersionFromSchedules(
            $doctor,
            [
                ['day_of_week' => 'monday', 'start_time' => '09:00', 'end_time' => '12:00'],
                ['day_of_week' => 'tuesday', 'start_time' => '09:00', 'end_time' => '12:00'],
                ['day_of_week' => 'wednesday', 'start_time' => '09:00', 'end_time' => '12:00'],
                ['day_of_week' => 'thursday', 'start_time' => '09:00', 'end_time' => '12:00'],
                ['day_of_week' => 'friday', 'start_time' => '09:00', 'end_time' => '12:00'],
            ],
            30,
            Carbon::today(),
            $adminUserId,
        );

        $versionService->createVersionFromSchedules(
            $doctor,
            [
                ['day_of_week' => 'monday', 'start_time' => '10:00', 'end_time' => '14:00'],
                ['day_of_week' => 'wednesday', 'start_time' => '10:00', 'end_time' => '14:00'],
                ['day_of_week' => 'friday', 'start_time' => '10:00', 'end_time' => '14:00'],
            ],
            30,
            Carbon::today()->addDays(7),
            $adminUserId,
        );

        $currentDates = [
            ['day_of_week' => 'monday', 'status' => AppointmentStatus::COMPLETED],
            ['day_of_week' => 'wednesday', 'status' => AppointmentStatus::PENDING],
            ['day_of_week' => 'friday', 'status' => AppointmentStatus::NO_SHOW],
        ];

        $futureDates = [
            ['day_of_week' => 'monday', 'status' => AppointmentStatus::PENDING],
            ['day_of_week' => 'friday', 'status' => AppointmentStatus::PENDING],
        ];

        foreach ($currentDates as $definition) {
            $date = $this->nextAgendaDateAvoidingVacations($doctor, Carbon::today(), $definition['day_of_week']);

            $this->seedAgendaAppointment($doctor, $patients->random()->patient, $faker, $date, $definition['status']);
        }

        foreach ($futureDates as $definition) {
            $date = $this->nextAgendaDateAvoidingVacations($doctor, Carbon::today()->addDays(7), $definition['day_of_week']);

            $this->seedAgendaAppointment($doctor, $patients->random()->patient, $faker, $date, $definition['status']);
        }
    }

    private function seedAgendaAppointment(Doctor $doctor, Patient $patient, \Faker\Generator $faker, Carbon $date, AppointmentStatus $status): void
    {
        $appointment = Appointment::create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $date->format('Y-m-d'),
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'status' => $status->value,
            'reason' => $faker->sentence(),
        ]);

        if ($status === AppointmentStatus::COMPLETED) {
            $consultation = Consultation::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'anamnesis' => 'Patient presented with ' . $faker->word() . ' pain. Vitals are stable. Advised rest and hydration.',
                'symptoms' => [$faker->word(), $faker->word()],
                'diagnosis' => 'General Fatigue',
                'next_visit_date' => $date->copy()->addDays(7)->format('Y-m-d'),
            ]);

            $consultation->prescriptionItems()->createMany([
                [
                    'medicine_name' => 'Amoxicillin',
                    'category' => 'Antibiotic',
                    'dosage' => '500mg',
                    'form_and_quantity' => '14 capsules',
                    'frequency' => 'Twice daily',
                    'duration' => '7 days',
                    'special_instructions' => 'Take after meals',
                    'storage_instructions' => 'Store in a cool dry place',
                    'side_effects' => 'Nausea or mild stomach upset',
                    'allergy_warnings' => 'Avoid if allergic to penicillin',
                ],
                [
                    'medicine_name' => 'Ibuprofen',
                    'category' => 'NSAID (Painkiller)',
                    'dosage' => '400mg',
                    'form_and_quantity' => '10 tablets',
                    'frequency' => 'As needed for pain',
                    'duration' => '3 days',
                    'special_instructions' => 'Do not exceed 3 tablets in 24 hours',
                    'storage_instructions' => null,
                    'side_effects' => null,
                    'allergy_warnings' => null,
                ],
            ]);

            Invoice::create([
                'appointment_id' => $appointment->id,
                'user_id' => $patient->user_id,
                'amount' => (float) ($doctor->session_price ?? 50.00),
                'invoice_number' => 'INV-' . strtoupper($faker->bothify('????-####')),
                'entry_type' => 'appointment_payment',
                'status' => 'paid',
                'paid_at' => $date->copy()->addHours(1),
            ]);
        }
    }

    private function nextDateOnOrAfter(Carbon $baseDate, string $dayOfWeek): Carbon
    {
        $date = $baseDate->copy()->startOfDay();

        while (strtolower($date->englishDayOfWeek) !== strtolower($dayOfWeek)) {
            $date->addDay();
        }

        return $date;
    }

    private function nextAgendaDateAvoidingVacations(Doctor $doctor, Carbon $baseDate, string $dayOfWeek): Carbon
    {
        $date = $this->nextDateOnOrAfter($baseDate, $dayOfWeek);

        while ($this->isVacationBlockedDate($doctor, $date)) {
            $blockingVacation = Vacation::query()
                ->where('doctor_id', $doctor->id)
                ->blocking()
                ->get()
                ->first(fn(Vacation $vacation) => $vacation->overlapsDate($date));

            if (!$blockingVacation) {
                break;
            }

            $date = $this->nextDateOnOrAfter(
                Carbon::parse($blockingVacation->end_date)->addDay(),
                $dayOfWeek
            );
        }

        return $date;
    }

    private function isVacationBlockedDate(Doctor $doctor, Carbon $date): bool
    {
        return Vacation::query()
            ->where('doctor_id', $doctor->id)
            ->blocking()
            ->get()
            ->contains(fn(Vacation $vacation) => $vacation->overlapsDate($date));
    }

    private function seedDoctorVacation(Doctor $doctor, Carbon $startDate, Carbon $endDate, string $submittedBy): void
    {
        Vacation::updateOrCreate(
            [
                'doctor_id' => $doctor->id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            [
                'status' => VacationStatus::APPROVED,
                'submitted_by' => $submittedBy,
            ]
        );
    }
}