<?php

namespace Database\Factories;

use App\Enums\Medical\DoctorSpecialization;
use App\Enums\Medical\Gender;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorFactory extends Factory
{
    public function definition(): array
    {
        return [
            'specialization' => $this->faker->randomElement(DoctorSpecialization::cases())->value,
            'bio' => $this->faker->paragraph(),
            'education' => $this->faker->randomElement([
                'Harvard Medical School', 'Johns Hopkins University', 'Oxford University', 'Stanford University',
            ]),
            'certification' => $this->faker->word().' Board Certified',
            'years_of_experience' => $this->faker->numberBetween(2, 30),
            'license_number' => $this->faker->unique()->numerify('MD-#####'),
            'gender' => $this->faker->randomElement([Gender::MALE->value, Gender::FEMALE->value]),

        ];
    }
}
