<?php

namespace App\Actions\Medical\Secretary;

use App\Enums\Medical\AppointmentStatus;
use App\Enums\UserStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use App\Notifications\EmergencyOverrideNotification;
use App\Services\FinancialService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class CreateEmergencyBookingAction
{
    public function __construct(private readonly FinancialService $financialService) {}

    public function execute(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {

            $patient = isset($data['patient_id'])
                ? Patient::findOrFail($data['patient_id'])
                : $this->createTemporaryPatient($data['patient_name'], $data['patient_phone'] ?? null);

            $date = Carbon::parse($data['date']);
            $startTime = $data['start_time'];

            $existingAppointment = Appointment::query()
                ->where('doctor_id', $data['doctor_id'])
                ->whereDate('appointment_date', $date)
                ->where('start_time', $startTime)
                ->where('status', '!=', AppointmentStatus::CANCELLED->value)
                ->lockForUpdate()
                ->first();

            if ($existingAppointment && $existingAppointment->reason === 'Emergency') {
                throw new BusinessLogicException('Cannot override an existing emergency appointment.');
            }

            if ($existingAppointment) {
                $this->financialService->refundForEmergencyOverride($existingAppointment);

                $existingAppointment->update([
                    'status' => AppointmentStatus::CANCELLED->value,
                ]);

                $existingAppointment->patient->user->notify(
                    new EmergencyOverrideNotification($existingAppointment)
                );
            }

            $slotDuration = 30; // ⚠️ شوف الملاحظة تحت
            $endTime = Carbon::parse($startTime)->addMinutes($slotDuration)->format('H:i');

            $appointment = Appointment::create([
                'patient_id' => $patient->id,
                'doctor_id' => $data['doctor_id'],
                'appointment_date' => $date->format('Y-m-d'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => AppointmentStatus::PENDING->value,
                'reason' => 'Emergency',
            ]);

            $patient->user->increment('wallet_balance', $appointment->doctor->session_price);

            $this->financialService->payForAppointmentAndCreateInvoice(
                $appointment->loadMissing(['doctor.user', 'patient.user'])
            );

            return $appointment;
        });
    }

    private function createTemporaryPatient(string $name, ?string $phone): Patient
    {
        $nameParts = explode(' ', trim($name), 2);
        $firstName = $nameParts[0] ?? $name;
        $lastName = $nameParts[1] ?? 'Emergency';

        $user = null;

        try {
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'email' => 'emergency_'.Str::random(12).'@temp.local',
                'password' => Hash::make(Str::random(16)),
                'user_status' => UserStatus::PENDING->value,
            ]);
        } catch (\Throwable $e) {
            // لو الفون مكرر (unique) منجرب تاني بدون فون عشان ما ينكسر السستم
            $user = User::create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => null,
                'email' => 'emergency_'.Str::random(12).'@temp.local',
                'password' => Hash::make(Str::random(16)),
                'user_status' => UserStatus::PENDING->value,
            ]);
        }

        return Patient::create([
            'user_id' => $user->id,
        ]);
    }
}
