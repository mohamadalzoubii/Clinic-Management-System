<?php

namespace App\DTOs\Secretary;

use App\Http\Requests\Admin\UpdateSecretaryRequest;
use Illuminate\Http\Request;

readonly class UpdateSecretaryData
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?string $phone,
        public ?string $password,
        public ?string $workDays,
        public ?float $monthlySalary,
    ) {}

    public static function formRequest(Request $request): self
    {
        $validated = $request->validated();

        return new self(
            firstName: $validated['first_name'] ?? null,
            lastName: $validated['last_name'] ?? null,
            email: $validated['email'] ?? null,
            phone: $validated['phone'] ?? null,
            password: $validated['password'] ?? null,
            workDays: $validated['work_days'] ?? null,
            monthlySalary: isset($validated['monthly_salary']) ? (float) $validated['monthly_salary'] : null,
        );
    }

    public function hasUserChanges(): bool
    {
        return $this->firstName !== null
            || $this->lastName !== null
            || $this->email !== null
            || $this->phone !== null
            || $this->password !== null;
    }

    public function hasSecretaryChanges(): bool
    {
        return $this->workDays !== null
            || $this->monthlySalary !== null;
    }
}
