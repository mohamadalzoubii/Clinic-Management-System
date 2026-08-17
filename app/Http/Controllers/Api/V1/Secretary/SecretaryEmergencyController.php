<?php

namespace App\Http\Controllers\Api\V1\Secretary;

use App\Actions\Medical\Doctor\GetDoctorAvailableDaysAction;
use App\Actions\Medical\Doctor\GetDoctorAvailableSlotsAction;
use App\Actions\Medical\Secretary\BlockSlotAction;
use App\Actions\Medical\Secretary\CreateEmergencyBookingAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Secretary\BlockSlotRequest;
use App\Http\Requests\Secretary\EmergencyBookRequest;
use App\Models\Doctor;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SecretaryEmergencyController extends Controller
{
    public function doctors()
    {
        $doctors = Doctor::with('user')->get();

        return response()->json(['data' => $doctors]);
    }

    public function availableSlots(Request $request, Doctor $doctor, GetDoctorAvailableSlotsAction $action)
    {
        $date = $request->query('date');

        $slots = $action->execute((string) $doctor->id, $date);

        if (Carbon::parse($date)->isToday()) {
            $now = Carbon::now()->format('H:i');
            $slots = array_values(array_filter($slots, function ($slot) use ($now) {
                $slotTime = is_array($slot) ? ($slot['start_time'] ?? $slot['time'] ?? null) : $slot;

                return $slotTime > $now;
            }));
        }

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
}
