<?php

namespace App\DTOs\Doctor;

use App\Enums\Medical\Gender;
use App\Http\Requests\Doctor\UpdateDoctorRequest;
use Illuminate\Http\Request;

readonly class UpdateDoctorData
{
    public function __construct(
        public ?string $firstName,
        public ?string $lastName,
        public ?string $email,
        public ?string $phone,
        public ?string $password,
        public ?string $specialization,
        public ?string $bio,
        public ?string $education,
        public ?string $certification,
        public ?int $yearsOfExperience,
        public ?float $sessionPrice,
        public ?string $licenseNumber,
        public ?Gender $gender,
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
            specialization: $validated['specialization'] ?? null,
            bio: $validated['bio'] ?? null,
            education: $validated['education'] ?? null,
            certification: $validated['certification'] ?? null,
            yearsOfExperience: $validated['years_of_experience'] ?? null,
            sessionPrice: $validated['session_price'] ?? null,
            licenseNumber: $validated['license_number'] ?? null,
            gender: isset($validated['gender']) ? Gender::from($validated['gender']) : null,
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

    public function hasDoctorChanges(): bool
    {
        return $this->specialization !== null
            || $this->bio !== null
            || $this->education !== null
            || $this->certification !== null
            || $this->yearsOfExperience !== null
            || $this->sessionPrice !== null
            || $this->licenseNumber !== null
            || $this->gender !== null;
    }
}
