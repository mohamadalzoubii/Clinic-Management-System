<?php

namespace App\Actions\Medical\Doctor;

use App\Models\Appointment;
use App\Services\Medical\DoctorScheduleVersionService;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\Log;

class GetDoctorAgendaAction
{
    public function __construct(private readonly DoctorScheduleVersionService $versionService) {}

    public function execute(int $doctorId, int $daysAhead = 7): array
    {
        $startDate = Carbon::today();
        $lookAheadDays = max($daysAhead * 14, 30);
        $searchEndDate = Carbon::today()->addDays($lookAheadDays - 1);
        $versions = $this->versionService->versionsForDoctor($doctorId);

        if ($versions->isEmpty()) {
            return [];
        }

        $appointments = Appointment::ForDoctorAgenda(
            $doctorId,
            $startDate->format('Y-m-d'),
            $searchEndDate->format('Y-m-d'),
        )
            ->get()
            ->groupBy(fn ($appointment) => Carbon::parse($appointment->appointment_date)->format('Y-m-d'));

        $agenda = [];
        $period = CarbonPeriod::create($startDate, $searchEndDate);

        foreach ($period as $date) {
            $dayName = strtolower($date->englishDayOfWeek);
            $dateString = $date->format('Y-m-d');
            $version = $this->versionService->resolveVersionFromCollection($versions, $date);
            $versionItem = $version ? $this->versionService->resolveItemFromCollection($versions, $date) : null;

            if (! $versionItem) {
                continue;
            }

            if (config('app.debug')) {
                Log::debug('Doctor agenda version selected', [
                    'doctor_id' => $doctorId,
                    'date' => $dateString,
                    'version_id' => $version?->id,
                    'effective_from_date' => $version?->effective_from_date?->toDateString(),
                ]);
            }

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
                        'appointment_date' => Carbon::parse($app->appointment_date)->format('Y-m-d'),
                        'start_time' => Carbon::parse($app->start_time)->format('H:i'),
                        'end_time' => Carbon::parse($app->end_time)->format('H:i'),
                        'status' => $app->status,
                        'reason' => $app->reason,
                    ];
                })->values()->toArray(),
            ];

            if (count($agenda) >= $daysAhead) {
                break;
            }
        }

        return $agenda;
    }
}
