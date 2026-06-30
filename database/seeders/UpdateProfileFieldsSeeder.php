<?php

namespace Database\Seeders;

use App\Models\Patient;
use App\Models\User;
use Faker\Factory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateProfileFieldsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->seedUserPhone();
        $this->seedPatientProfiles();
    }

    private function seedUserPhone(): void
    {
        $faker = Factory::create();

        $users = User::whereNull('phone')->get();
        foreach ($users as $user) {
            $user->update([
                'phone' => $faker->phoneNumber(),
            ]);
        }

        $this->command->info('User phone seeded: ' . $users->count() . ' users.');
    }

    private function seedPatientProfiles(): void
    {
        $faker = Factory::create();
        $relationships = ['spouse', 'parent', 'sibling', 'child', 'friend', 'other'];
        $statuses = ['yes', 'no', 'occasionally'];

        $patients = Patient::whereNull('city')
            ->orWhereNull('emergency_contact_relationship')
            ->orWhereNull('smoking_status')
            ->orWhereNull('alcohol_status')
            ->get();

        foreach ($patients as $patient) {
            $patient->update([
                'city' => $faker->city(),
                'emergency_contact_relationship' => $relationships[array_rand($relationships)],
                'emergency_contact_email' => $faker->email(),
                'emergency_contact_city' => $faker->city(),
                'smoking_status' => $statuses[array_rand($statuses)],
                'alcohol_status' => $statuses[array_rand($statuses)],
            ]);
        }

        $this->command->info('Patient profiles seeded: ' . $patients->count() . ' patients.');
    }
}
