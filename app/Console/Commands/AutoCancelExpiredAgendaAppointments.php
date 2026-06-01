<?php

namespace App\Console\Commands;

use App\Actions\Appointment\CancelAppointmentAction;
use App\Enums\Medical\AppointmentStatus;
use App\Models\Appointment;
use App\Services\Medical\DoctorScheduleVersionService;
use App\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoCancelExpiredAgendaAppointments extends Command
{
    protected $signature = 'appointments:auto-cancel-expired-agenda';

    protected $description = 'Cancel pending appointments that are no longer part of the active agenda window.';

    public function handle(
        DoctorScheduleVersionService $versionService,
        VacationService $vacationService,
        CancelAppointmentAction $cancelAppointmentAction,
    ): int {
        $today = Carbon::today();
        $cancelledCount = 0;

        Appointment::query()
            ->where('status', AppointmentStatus::PENDING->value)
            ->with(['doctor.user', 'patient.user'])
            ->orderBy('id')
            ->get()
            ->each(function (Appointment $appointment) use (
                $today,
                $versionService,
                $vacationService,
                $cancelAppointmentAction,
                &$cancelledCount,
            ) {
                $appointmentDate = Carbon::parse($appointment->appointment_date)->startOfDay();
                $doctorId = (int) $appointment->doctor_id;

                if ($appointmentDate->lt($today) || $this->isNoLongerInAgenda($doctorId, $appointmentDate, $versionService, $vacationService)) {
                    $cancelAppointmentAction->executeForDoctor($appointment, $doctorId);
                    $cancelledCount++;
                }
            });

        $this->info("Auto-cancelled {$cancelledCount} expired agenda appointments.");

        return self::SUCCESS;
    }

    private function isNoLongerInAgenda(
        int $doctorId,
        Carbon $date,
        DoctorScheduleVersionService $versionService,
        VacationService $vacationService,
    ): bool {
        if ($vacationService->isBlockingDate($doctorId, $date, false)) {
            return true;
        }

        return $versionService->resolveItemForDate($doctorId, $date) === null;
    }
}