<?php

namespace Database\Seeders;

use App\Enums\Medical\AppointmentStatus;
use App\Enums\RoleEnum;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Patient;
use App\Models\Review;
use App\Models\User;
use Carbon\Carbon;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\PermissionRegistrar;

// تأكد من وجود المودل

// تأكد من وجود المودل

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Reset Spatie Cache
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // 2. Create Roles
        $this->call([RoleSeeder::class]);
        $doctorUser = User::factory()->create([

            'first_name' => 'doctor',

            'last_name' => 'Test',

            'email' => 'doctor@test.com',

            'password' => Hash::make('password'),

            'user_status' => 'approved',

        ]);

        $doctorUser->assignRole(RoleEnum::DOCTOR->value);

        $doctor = Doctor::factory()->create(['user_id' => $doctorUser->id]);

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

        // 🔥
        // 3. Create Packages
        $packages = [
            ['name' => 'Basic Wellness', 'price' => 50, 'balance_amount' => 60],
            ['name' => 'Premium Care', 'price' => 100, 'balance_amount' => 125],
            ['name' => 'VIP Family', 'price' => 200, 'balance_amount' => 260],
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
                'wallet_balance' => rand(50, 300), // إعطاء رصيد عشوائي للمرضى
            ])
            ->each(fn ($user) => $user->assignRole(RoleEnum::PATIENT->value));

        // 5. Create 10 Doctors (With Schedules)
        $doctors = User::factory(10)
            ->has(Doctor::factory()->has(DoctorSchedule::factory()->count(4), 'schedules'), 'doctor')
            ->create([
                'user_status' => 'approved',
                'password' => Hash::make('password'),
            ])
            ->each(fn ($user) => $user->assignRole(RoleEnum::DOCTOR->value));

        // 6. Generate "The Living System" (Appointments, Consultations, Prescriptions, Invoices, Reviews)
        $faker = Factory::create();
        $appointmentStatuses = AppointmentStatus::cases();

        // Loop through each doctor to give them an active history
        foreach ($doctors as $docUser) {
            $doctor = $docUser->doctor;

            // Create 5 to 15 appointments per doctor
            $numAppointments = rand(5, 15);

            for ($i = 0; $i < $numAppointments; $i++) {
                $randomPatient = $patients->random()->patient;
                $status = $faker->randomElement($appointmentStatuses)->value;

                // Mix of past and future dates
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

                // B. If Completed -> Generate Consultation, Rx, Invoice, and Review
                if ($status === AppointmentStatus::COMPLETED->value) {

                    // 1. Consultation
                    $consultation = Consultation::create([
                        'appointment_id' => $appointment->id,
                        'doctor_id' => $doctor->id,
                        'patient_id' => $randomPatient->id,
                        'notes' => 'Patient presented with '.$faker->word().' pain. Vitals are stable. Advised rest and hydration.',
                        'next_visit_date' => $date->copy()->addDays(7)->format('Y-m-d'),
                    ]);

                    // 2. Prescription Items
                    $consultation->prescriptionItems()->createMany([
                        [
                            'medicine_name' => 'Amoxicillin',
                            'dosage' => '500mg',
                            'frequency' => 'Twice daily',
                            'duration' => '7 days',
                            'notes' => 'Take after meals',
                        ],
                        [
                            'medicine_name' => 'Ibuprofen',
                            'dosage' => '400mg',
                            'frequency' => 'As needed for pain',
                            'duration' => '3 days',
                            'notes' => 'Do not exceed 3 tablets in 24 hours',
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

                    // 4. Review (Assuming you have a reviews table linked to doctor and patient)
                    // Uncomment and adjust if you have a Review model

                    //                    DoctorReview::firstOrCreate(
                    //                        [
                    //                            // 🔍 محددات البحث
                    //                            'doctor_id' => $doctor->id,
                    //                            'patient_id' => $randomPatient->id,
                    //                        ],
                    //                        [
                    //                            // ✍️ البيانات الجديدة
                    //                            //                            'appointment_id' => $appointment->id,
                    //                            'rating' => rand(3, 5),
                    //                            'comment' => $faker->randomElement([
                    //                                'Great doctor, very attentive.',
                    //                                'The clinic was clean and the staff was friendly.',
                    //                                'Highly recommended!',
                    //                                'Doctor took the time to explain everything clearly.',
                    //                            ]),
                    //                        ]
                    //                    );

                }
            }
        }
    }
}
