<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'date_of_birth' => $this->faker->date('Y-m-d', '2005-01-01'),
            'emergency_contact_name' => $this->faker->name(),
            'emergency_contact_phone' => $this->faker->phoneNumber(),
            'allergies' => $this->faker->randomElement(['None', 'Penicillin', 'Peanuts', 'Dust', 'Latex']),
            'chronic_diseases' => $this->faker->randomElement(['None', 'Type 2 Diabetes', 'Hypertension', 'Asthma']),
            'weight' => $this->faker->randomFloat(2, 50, 120),
            'height' => $this->faker->randomFloat(2, 150, 195),
            'gender' => $this->faker->randomElement(['male', 'female']),
            'blood_type' => $this->faker->randomElement(['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']),
            'is_smoker' => $this->faker->boolean(20),
            'drinks_alcohol' => $this->faker->boolean(10),
        ];
    }
}
