<?php

namespace App\DTOs\Doctor;

use App\Enums\Medical\Gender;
use App\Http\Requests\Doctor\StoreDoctorRequest;
use Illuminate\Http\Request;

readonly class StoreDoctorData
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public ?string $phone,
        public string $password,
        public string $specialization,
        public string $bio,
        public string $education,
        public string $certification,
        public int $yearsOfExperience,
        public float $sessionPrice,
        public string $licenseNumber,
        public Gender $gender,
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
            specialization: $validated['specialization'],
            bio: $validated['bio'],
            education: $validated['education'],
            certification: $validated['certification'],
            yearsOfExperience: $validated['years_of_experience'],
            sessionPrice: $validated['session_price'],
            licenseNumber: $validated['license_number'],
            gender: Gender::from($validated['gender']),
        );
    }
}
