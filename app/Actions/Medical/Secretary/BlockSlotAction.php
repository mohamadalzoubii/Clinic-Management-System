<?php

namespace App\Actions\Medical\Secretary;

use App\Enums\Medical\AppointmentStatus;
use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Notifications\EmergencyOverrideNotification;
use App\Services\FinancialService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BlockSlotAction
{
    public function __construct(private readonly FinancialService $financialService) {}

    public function execute(array $data): BlockedSlot
    {
        return DB::transaction(function () use ($data) {

            $date = Carbon::parse($data['date']);

            $existingAppointment = Appointment::query()
                ->where('doctor_id', $data['doctor_id'])
                ->whereDate('appointment_date', $date)
                ->where('start_time', $data['start_time'])
                ->where('status', '!=', AppointmentStatus::CANCELLED->value)
                ->lockForUpdate()
                ->first();

            if ($existingAppointment) {
                $this->financialService->refundForEmergencyOverride($existingAppointment);

                $existingAppointment->update([
                    'status' => AppointmentStatus::CANCELLED->value,
                ]);

                $existingAppointment->patient->user->notify(
                    new EmergencyOverrideNotification($existingAppointment)
                );
            }

            return BlockedSlot::firstOrCreate([
                'doctor_id' => $data['doctor_id'],
                'date' => $date->format('Y-m-d'),
                'start_time' => $data['start_time'],
            ]);
        });
    }
}
