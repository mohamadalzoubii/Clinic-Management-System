<?php

namespace Database\Seeders;

use App\Enums\Medical\AppointmentStatus;
use App\Enums\Medical\DoctorSpecialization;
use App\Enums\Medical\Gender;
use App\Enums\Medical\VacationStatus;
use App\Enums\RoleEnum;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Patient;
use App\Models\User;
use App\Models\Vacation;
use App\Services\Medical\DoctorScheduleVersionService;
use Carbon\Carbon;
use Faker\Factory;
use Faker\Generator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
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
            'password' => Hash::make('Password12'),
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
            'password' => Hash::make('Zain1234'),
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
                'password' => Hash::make('Password12'),
                'wallet_balance' => rand(50, 300),
            ]);

        foreach ($patients as $user) {
            $user->assignRole(RoleEnum::PATIENT->value);
        }

        // 5. Create 10 Doctors with realistic, RAG-friendly profiles (With Schedules)
        $doctorProfiles = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Mitchell',
                'email' => 'sarah.mitchell@test.com',
                'gender' => Gender::FEMALE->value,
                'specialization' => DoctorSpecialization::GENERAL_PRACTITIONER->value,
                'bio' => 'Dr. Sarah Mitchell is a general practitioner focused on preventive care, chronic disease management, and routine health screenings. She works closely with patients on long-term wellness plans covering diabetes, hypertension, and cholesterol management, and is known for taking extra time to explain treatment options in plain language.',
                'education' => 'Johns Hopkins University School of Medicine',
                'certification' => 'American Board of Family Medicine Certified',
                'years_of_experience' => 14,
                'license_number' => 'MD-10234',
                'session_price' => 45.00,
            ],
            [
                'first_name' => 'James',
                'last_name' => 'Whitfield',
                'email' => 'james.whitfield@test.com',
                'gender' => Gender::MALE->value,
                'specialization' => DoctorSpecialization::CARDIOLOGIST->value,
                'bio' => 'Dr. James Whitfield is a cardiologist specializing in hypertension management, arrhythmia diagnosis, and post-heart-attack rehabilitation. He has extensive experience interpreting ECGs and stress tests, and regularly coordinates with patients on lifestyle changes to reduce cardiovascular risk.',
                'education' => 'Harvard Medical School',
                'certification' => 'American Board of Internal Medicine, Cardiovascular Disease Certified',
                'years_of_experience' => 21,
                'license_number' => 'MD-10577',
                'session_price' => 75.00,
            ],
            [
                'first_name' => 'Layla',
                'last_name' => 'Haddad',
                'email' => 'layla.haddad@test.com',
                'gender' => Gender::FEMALE->value,
                'specialization' => DoctorSpecialization::DENTIST->value,
                'bio' => 'Dr. Layla Haddad is a dentist with a focus on restorative dentistry, root canal treatment, and pediatric dental care. She has a gentle approach with anxious patients and children, and regularly performs cavity fillings, crowns, and routine dental cleanings.',
                'education' => 'University of Pennsylvania School of Dental Medicine',
                'certification' => 'American Board of General Dentistry Certified',
                'years_of_experience' => 9,
                'license_number' => 'MD-10812',
                'session_price' => 40.00,
            ],
            [
                'first_name' => 'Omar',
                'last_name' => 'Nasser',
                'email' => 'omar.nasser@test.com',
                'gender' => Gender::MALE->value,
                'specialization' => DoctorSpecialization::OPHTHALMOLOGIST->value,
                'bio' => 'Dr. Omar Nasser is an ophthalmologist specializing in cataract surgery, glaucoma management, and vision correction. He sees patients for routine eye exams as well as more complex retinal conditions, and is experienced with both surgical and non-surgical treatment plans.',
                'education' => 'Stanford University School of Medicine',
                'certification' => 'American Board of Ophthalmology Certified',
                'years_of_experience' => 17,
                'license_number' => 'MD-11045',
                'session_price' => 60.00,
            ],
            [
                'first_name' => 'Emily',
                'last_name' => 'Carter',
                'email' => 'emily.carter@test.com',
                'gender' => Gender::FEMALE->value,
                'specialization' => DoctorSpecialization::NEUROLOGIST->value,
                'bio' => 'Dr. Emily Carter is a neurologist who treats migraines, epilepsy, and peripheral neuropathy. She has particular interest in chronic headache disorders and works with patients on both medication management and lifestyle-based trigger avoidance.',
                'education' => 'University of Oxford Medical School',
                'certification' => 'American Board of Psychiatry and Neurology Certified',
                'years_of_experience' => 12,
                'license_number' => 'MD-11298',
                'session_price' => 70.00,
            ],
            [
                'first_name' => 'Rana',
                'last_name' => 'Khalil',
                'email' => 'rana.khalil@test.com',
                'gender' => Gender::FEMALE->value,
                'specialization' => DoctorSpecialization::GYNECOLOGIST->value,
                'bio' => 'Dr. Rana Khalil is a gynecologist providing prenatal care, routine gynecological exams, and fertility consultations. She has delivered hundreds of babies and is known for her calm, reassuring manner with first-time mothers.',
                'education' => 'University of Toronto Faculty of Medicine',
                'certification' => 'American Board of Obstetrics and Gynecology Certified',
                'years_of_experience' => 16,
                'license_number' => 'MD-11563',
                'session_price' => 65.00,
            ],
            [
                'first_name' => 'Michael',
                'last_name' => 'Reyes',
                'email' => 'michael.reyes@test.com',
                'gender' => Gender::MALE->value,
                'specialization' => DoctorSpecialization::UROLOGIST->value,
                'bio' => 'Dr. Michael Reyes is a urologist treating kidney stones, urinary tract infections, and prostate conditions. He performs both diagnostic evaluations and minimally invasive procedures, and works with older male patients on prostate health screening.',
                'education' => 'University of Chicago Pritzker School of Medicine',
                'certification' => 'American Board of Urology Certified',
                'years_of_experience' => 19,
                'license_number' => 'MD-11827',
                'session_price' => 68.00,
            ],
            [
                'first_name' => 'Nour',
                'last_name' => 'Saab',
                'email' => 'nour.saab@test.com',
                'gender' => Gender::FEMALE->value,
                'specialization' => DoctorSpecialization::OTOLARYNGOLOGIST->value,
                'bio' => 'Dr. Nour Saab is an ear, nose, and throat specialist treating chronic sinusitis, tonsillitis, and hearing-related issues. She sees a large number of pediatric ENT cases and also handles minor in-office procedures such as ear wax removal and nasal endoscopy.',
                'education' => 'McGill University Faculty of Medicine',
                'certification' => 'American Board of Otolaryngology Certified',
                'years_of_experience' => 8,
                'license_number' => 'MD-12091',
                'session_price' => 55.00,
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Kim',
                'email' => 'david.kim@test.com',
                'gender' => Gender::MALE->value,
                'specialization' => DoctorSpecialization::PULMONOLOGIST->value,
                'bio' => 'Dr. David Kim is a pulmonologist specializing in asthma, COPD, and sleep apnea management. He runs pulmonary function tests regularly and works closely with patients on inhaler technique and long-term breathing management plans.',
                'education' => 'University of Michigan Medical School',
                'certification' => 'American Board of Internal Medicine, Pulmonary Disease Certified',
                'years_of_experience' => 13,
                'license_number' => 'MD-12354',
                'session_price' => 62.00,
            ],
            [
                'first_name' => 'Yasmin',
                'last_name' => 'Farouk',
                'email' => 'yasmin.farouk@test.com',
                'gender' => Gender::FEMALE->value,
                'specialization' => DoctorSpecialization::GASTROENTEROLOGIST->value,
                'bio' => 'Dr. Yasmin Farouk is a gastroenterologist treating acid reflux, irritable bowel syndrome, and inflammatory bowel disease. She performs routine endoscopies and colonoscopies and takes a diet-focused approach alongside standard medical treatment.',
                'education' => 'Imperial College London Faculty of Medicine',
                'certification' => 'American Board of Internal Medicine, Gastroenterology Certified',
                'years_of_experience' => 11,
                'license_number' => 'MD-12618',
                'session_price' => 63.00,
            ],
        ];

        $doctors = collect($doctorProfiles)->map(function (array $profile) {
            $user = User::factory()->create([
                'first_name' => $profile['first_name'],
                'last_name' => $profile['last_name'],
                'email' => $profile['email'],
                'user_status' => 'approved',
                'password' => Hash::make('Password12'),
            ]);

            $user->assignRole(RoleEnum::DOCTOR->value);

            $doctor = Doctor::factory()->create([
                'user_id' => $user->id,
                'specialization' => $profile['specialization'],
                'bio' => $profile['bio'],
                'education' => $profile['education'],
                'certification' => $profile['certification'],
                'years_of_experience' => $profile['years_of_experience'],
                'license_number' => $profile['license_number'],
                'gender' => $profile['gender'],
                'session_price' => $profile['session_price'],
            ]);

            DoctorSchedule::factory()->count(4)->create(['doctor_id' => $doctor->id]);

            return $user;
        });

        // 5b. Create 1 Dedicated X-Ray Specialist (Radiologist)
        $radiologistUser = User::factory()->create([
            'first_name' => 'Radiologist',
            'last_name' => 'Specialist',
            'email' => 'radiologist@test.com',
            'password' => Hash::make('Password12'),
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
            'password' => Hash::make('Password12'),
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
        //    plus deterministic extra history for demo/testing.
        $appointmentStatuses = array_filter(
            AppointmentStatus::cases(),
            fn (AppointmentStatus $status) => $status !== AppointmentStatus::CONFIRMED,
        );

        // Ensure Patient "Patient Test" has at least 3 COMPLETED appointments with Doctor "doctor Test".
        // Requirement: add 3 more completed appointments (do NOT delete existing ones).
        $this->seedAdditionalCompletedAppointments(
            doctor: $doctor,
            patient: $patient,
            count: 3
        );

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
                        'anamnesis' => 'Patient presented with '.$faker->word().' pain. Vitals are stable. Advised rest and hydration.',
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
                        'invoice_number' => 'INV-'.strtoupper($faker->bothify('????-####')),
                        'status' => 'paid',
                        'paid_at' => $date->copy()->addHours(1),
                    ]);
                }
            }
        }
        $this->call([AddDoctorReviewsSeeder::class]);
        $this->call([UpdateProfileFieldsSeeder::class]);
        $this->call([AddConsultationsSeeder::class]);
        $this->call([AddPatientMediaSeeder::class]);
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
            $date = $this->nextAgendaDateAvoidingVacations($doctor, Carbon::today()->addDays(7),
                $definition['day_of_week']);

            $this->seedAgendaAppointment($doctor, $patients->random()->patient, $faker, $date, $definition['status']);
        }
    }

    private function nextAgendaDateAvoidingVacations(Doctor $doctor, Carbon $baseDate, string $dayOfWeek): Carbon
    {
        $date = $this->nextDateOnOrAfter($baseDate, $dayOfWeek);

        while ($this->isVacationBlockedDate($doctor, $date)) {
            $blockingVacation = Vacation::query()
                ->where('doctor_id', $doctor->id)
                ->blocking()
                ->get()
                ->first(fn (Vacation $vacation) => $vacation->overlapsDate($date));

            if (! $blockingVacation) {
                break;
            }

            $date = $this->nextDateOnOrAfter(
                Carbon::parse($blockingVacation->end_date)->addDay(),
                $dayOfWeek
            );
        }

        return $date;
    }

    private function nextDateOnOrAfter(Carbon $baseDate, string $dayOfWeek): Carbon
    {
        $date = $baseDate->copy()->startOfDay();

        while (strtolower($date->englishDayOfWeek) !== strtolower($dayOfWeek)) {
            $date->addDay();
        }

        return $date;
    }

    private function isVacationBlockedDate(Doctor $doctor, Carbon $date): bool
    {
        return Vacation::query()
            ->where('doctor_id', $doctor->id)
            ->blocking()
            ->get()
            ->contains(fn (Vacation $vacation) => $vacation->overlapsDate($date));
    }

    private function seedAgendaAppointment(
        Doctor $doctor,
        Patient $patient,
        Generator $faker,
        Carbon $date,
        AppointmentStatus $status
    ): void {
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
                'anamnesis' => 'Patient presented with '.$faker->word().' pain. Vitals are stable. Advised rest and hydration.',
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
                'invoice_number' => 'INV-'.strtoupper($faker->bothify('????-####')),
                'entry_type' => 'appointment_payment',
                'status' => 'paid',
                'paid_at' => $date->copy()->addHours(1),
            ]);
        }
    }

    private function seedAdditionalCompletedAppointments(Doctor $doctor, Patient $patient, int $count): void
    {
        $faker = Factory::create();

        // Create 3 additional completed appointments deterministically and idempotently.
        // If the seeder is re-run, we avoid duplicates by checking (doctor_id, patient_id, appointment_date, start_time, status).
        $baseDate = Carbon::now()->subDays(90);

        for ($i = 0; $i < $count; $i++) {
            $date = $baseDate->copy()->addDays($i * 7);
            $startTime = '10:00:00';
            $endTime = '10:30:00';

            $existing = Appointment::query()
                ->where('doctor_id', $doctor->id)
                ->where('patient_id', $patient->id)
                ->where('appointment_date', $date->format('Y-m-d'))
                ->where('start_time', $startTime)
                ->where('end_time', $endTime)
                ->where('status', AppointmentStatus::COMPLETED->value)
                ->exists();

            if ($existing) {
                continue;
            }

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $date->format('Y-m-d'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => AppointmentStatus::COMPLETED->value,
                'reason' => $faker->sentence(),
            ]);

            $consultation = Consultation::create([
                'appointment_id' => $appointment->id,
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'anamnesis' => 'Patient presented with '.$faker->word().' pain. Vitals are stable. Advised rest and hydration.',
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
                'invoice_number' => 'INV-'.strtoupper($faker->bothify('????-####')),
                'entry_type' => 'appointment_payment',
                'status' => 'paid',
                'paid_at' => $date->copy()->addHours(1),
            ]);
        }
    }
}
