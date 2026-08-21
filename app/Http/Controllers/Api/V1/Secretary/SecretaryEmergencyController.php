<?php

namespace App\Http\Controllers\Api\V1\Secretary;

use App\Actions\Medical\Doctor\GetDoctorAvailableDaysAction;
use App\Actions\Medical\Doctor\GetDoctorAvailableSlotsAction;
use App\Actions\Medical\Secretary\BlockSlotAction;
use App\Actions\Medical\Secretary\CreateEmergencyBookingAction;
use App\Actions\Medical\Secretary\GetAllSlotsForSecretaryAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\BlockSlotRequest;
use App\Http\Requests\Secretary\EmergencyBookRequest;
use App\Enums\Medical\DoctorSpecialization;
use App\Enums\Medical\AppointmentStatus;
use App\Models\Doctor;
use App\Models\Appointment;
use App\Models\BlockedSlot;
use App\Services\Medical\DoctorScheduleVersionService;
use App\Services\TimeSlotGeneratorService;
use App\Services\VacationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SecretaryEmergencyController extends Controller
{

    public function doctors(VacationService $vacationService)
    {
        $excludedSpecializations = [
            DoctorSpecialization::RADIOLOGIST->value,
            DoctorSpecialization::PATHOLOGIST->value,
        ];

        $doctors = Doctor::with('user')
            ->whereNotIn('specialization', $excludedSpecializations)
            ->get()
            ->map(function (Doctor $doctor) use ($vacationService) {
                $doctor->setAttribute(
                    'on_leave',
                    $vacationService->isBlockingDate($doctor->id, Carbon::today())
                );

                return $doctor;
            });

        return response()->json(['data' => $doctors]);
    }

    public function doctorsCount()
    {
        $excludedSpecializations = [
            DoctorSpecialization::RADIOLOGIST->value,
            DoctorSpecialization::PATHOLOGIST->value,
        ];

        $doctorsCount = Doctor::whereNotIn('specialization', $excludedSpecializations)->count();

        return response()->json([
            'data' => [
                'doctors_count' => $doctorsCount,
            ],
        ]);
    }

    public function availableSlots(
        Request $request,
        Doctor $doctor,
        DoctorScheduleVersionService $versionService,
        TimeSlotGeneratorService $slotGenerator
    ) {
        $date = Carbon::parse($request->query('date'));
        $doctorId = (int) $doctor->id;

        // Get doctor's schedule
        $versions = $versionService->versionsForDoctor($doctorId);
        $schedule = $versionService->resolveItemFromCollection($versions, $date);

        if (!$schedule) {
            return response()->json(['data' => []]);
        }

        // Generate all slots
        $allSlots = $slotGenerator->generate(
            $schedule->start_time,
            $schedule->end_time,
            (int) $schedule->slot_duration,
            []
        );

        // Get emergency bookings only (to exclude them)
        $emergencyBookingTimes = Appointment::where('doctor_id', $doctorId)
            ->whereDate('appointment_date', $date)
            ->where('reason', 'Emergency')
            ->whereIn('status', [AppointmentStatus::PENDING->value])
            ->pluck('start_time')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

        // Get blocked slots
        $blockedSlots = BlockedSlot::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->pluck('start_time')
            ->map(fn ($time) => Carbon::parse($time)->format('H:i'))
            ->toArray();

        // Filter slots: exclude emergency bookings and blocked slots
        $slots = array_values(array_filter($allSlots, function ($slot) use ($emergencyBookingTimes, $blockedSlots, $date, $schedule) {
            $slotTime = is_array($slot) ? ($slot['start_time'] ?? $slot['time'] ?? $slot) : $slot;

            // Exclude emergency bookings and blocked slots
            if (in_array($slotTime, $emergencyBookingTimes) || in_array($slotTime, $blockedSlots)) {
                return false;
            }

            // If today, check if slot end time hasn't passed
            if ($date->isToday()) {
                $slotEnd = Carbon::parse($slotTime)->addMinutes((int) $schedule->slot_duration);
                if ($slotEnd->lte(Carbon::now())) {
                    return false;
                }
            }

            return true;
        }));

        return response()->json(['data' => $slots]);
    }

    public function availableDays(Doctor $doctor, GetDoctorAvailableDaysAction $action)
    {
        $days = $action->execute($doctor->id);

        return response()->json(['data' => $days]);
    }

    public function blockSlot(BlockSlotRequest $request, BlockSlotAction $action)
    {
        $blockedSlot = $action->execute($request->validated());

        return response()->json([
            'message' => 'The slot has been blocked.',
            'data' => $blockedSlot,
        ], 201);
    }

    public function emergencyBook(EmergencyBookRequest $request, CreateEmergencyBookingAction $action)
    {
        $appointment = $action->execute($request->validated());

        return response()->json([
            'message' => 'The appointment has been booked.',
            'data' => $appointment,
        ], 201);
    }

    public function allSlotsForBooking(Request $request, Doctor $doctor, GetAllSlotsForSecretaryAction $action)
    {
        $slots = $action->execute((string) $doctor->id, $request->query('date'));

        return response()->json(['data' => $slots]);
    }
}
