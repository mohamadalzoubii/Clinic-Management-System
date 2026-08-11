<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\UserStatus;
use App\Models\Secretary;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SecretaryAccountSeeder extends Seeder
{
    public function run(): void
    {
        $secretary = User::updateOrCreate(
            ['email' => 'secretary@test.com'],
            [
                'first_name' => 'Secretary',
                'last_name' => 'Test',
                'password' => Hash::make('Password12'),
                'user_status' => UserStatus::APPROVED->value,
            ]
        );

        if (! $secretary->hasRole(RoleEnum::SECRETARY->value)) {
            $secretary->assignRole(RoleEnum::SECRETARY->value);
        }

        Secretary::updateOrCreate(
            ['user_id' => $secretary->id],
            [
                'work_days' => 'monday,tuesday,wednesday,thursday,friday',
                'monthly_salary' => 3000,
            ]
        );
    }
}