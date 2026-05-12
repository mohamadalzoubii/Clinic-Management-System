<?php

namespace App\Actions\Medical\Doctor;

use App\Models\Appointment;
use App\Models\DoctorSchedule;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class GetDoctorAgendaAction
{
    public function execute(int $doctorId, int $daysAhead = 7): array
    {

        $workingDays = DoctorSchedule::ForDoctorActive($doctorId)
            ->pluck('day_of_week')
            ->map(fn ($day) => strtolower($day->value ?? $day))
            ->toArray();

        if (empty($workingDays)) {
            return [];
        }

        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays($daysAhead - 1);

        $appointments = Appointment::ForDoctorAgenda(
            $doctorId,
            $startDate->format('Y-m-d'),
            $endDate->format('Y-m-d'),
        )
            ->get()
            ->groupBy('appointment_date');

        $agenda = [];
        $period = CarbonPeriod::create($startDate, $endDate);

        foreach ($period as $date) {
            $dayName = strtolower($date->englishDayOfWeek);
            $dateString = $date->format('Y-m-d');

            if (in_array($dayName, $workingDays)) {

                $dayAppointments = $appointments->get($dateString, collect());

                $agenda[] = [
                    'full_date' => $dateString,
                    'day_name' => $date->format('D'),
                    'day_number' => $date->format('d'),
                    'month_name' => $date->format('M'),

                    'booked_appointments' => $dayAppointments->map(function ($app) {
                        return [
                            'appointment_id' => $app->id,
                            'patient_id' => $app->patient_id,
                            'patient_name' => $app->patient->user->first_name.' '.$app->patient->user->last_name,
                            'start_time' => Carbon::parse($app->start_time)->format('H:i'),
                            'end_time' => Carbon::parse($app->end_time)->format('H:i'),
                            'status' => $app->status,
                            'reason' => $app->reason,
                        ];
                    })->values()->toArray(),
                ];
            }
        }

        return $agenda;
    }
}
