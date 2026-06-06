<?php

namespace App\DTOs\Secretary;

use App\Http\Requests\Admin\StoreSecretaryRequest;
use Illuminate\Http\Request;

readonly class StoreSecretaryData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $phone,
        public string $password,
        public ?string $workDays,
        public ?float $monthlySalary,
    ) {}

    public static function formRequest(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            firstName: $validated['first_name'],
            lastName: $validated['last_name'],
            email: $validated['email'],
            phone: $validated['phone'] ?? null,
            password: $validated['password'],
            workDays: $validated['work_days'] ?? null,
            monthlySalary: isset($validated['monthly_salary']) ? (float) $validated['monthly_salary'] : null,
        );
    }
}
