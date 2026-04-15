<?php

namespace Database\Factories;

use App\Enums\Medical\DayOfWeek;
use App\Enums\Medical\ScheduleStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DoctorSchedule>
 */
class DoctorScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            
            'day_of_week'   => $this->faker->randomElement(DayOfWeek::cases())->value,
            'start_time'    => '08:00',
            'end_time'      => '14:00',
            'slot_duration' => 30, 
            'status'        => $this->faker->randomElement(ScheduleStatus::cases())->value,
        ];
    }
}
