<?php

namespace App\Services;

use App\DTOs\Invoice\PayAppointmentData;
use App\Enums\Medical\InvoiceStatus;
use App\Exceptions\BusinessLogicException;
use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FinancialService
{
    public function buyPackage(User $user, Package $package)
    {
        return DB::transaction(function () use ($user, $package) {
            $user->increment('wallet_balance', $package->balance_amount);

            $user->fresh();

            return $user;

        });
    }

    public function PayForAppointment(Appointment $appointment)
    {
        return DB::transaction(function () use ($appointment) {

            $this->Pay($appointment);

        });
    }

    public function Pay(Appointment $appointment)
    {
        $dto = PayAppointmentData::fromAppointment($appointment);

        if ($dto->patientUser->wallatBalance < $dto->price) {
            throw new BusinessLogicException('You dont have enough balance to pay for this appointment.');
        }

        $dto->patientUser->decrement('balance', $dto->price);

        return Invoice::create([
            'appointment_id' => $dto->appointment->id,
            'user_id' => $dto->patientUser->id,
            'amount' => $dto->price,
            'invoice_number' => 'INV-'.time().'-'.$dto->appointment->id,
            'status' => InvoiceStatus::PAID,
            'paid_at' => now(),
        ]);

    }
}
