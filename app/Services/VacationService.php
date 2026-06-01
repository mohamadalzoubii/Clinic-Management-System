<?php

namespace App\Services;

use App\Enums\Medical\VacationStatus;
use App\Models\Appointment;
use App\Models\Vacation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class VacationService
{
    public function vacationsTableExists(): bool
    {
        return Schema::hasTable('vacations');
    }

    public function lastAppointmentDay(int $doctorId): ?Carbon
    {
        $latestAppointmentDate = Appointment::where('doctor_id', $doctorId)->max('appointment_date');

        return $latestAppointmentDate ? Carbon::parse($latestAppointmentDate)->startOfDay() : null;
    }

    public function earliestAllowedStartDate(int $doctorId): Carbon
    {
        $lastAppointmentDay = $this->lastAppointmentDay($doctorId);

        if (! $lastAppointmentDay) {
            return Carbon::today()->addDays(14)->startOfDay();
        }

        return $lastAppointmentDay->copy()->addDays(14);
    }

    public function syncExpiredVacations(?int $doctorId = null): int
    {
        if (! $this->vacationsTableExists()) {
            return 0;
        }

        $today = Carbon::today();

        $query = Vacation::query()
            ->when($doctorId, fn ($builder) => $builder->where('doctor_id', $doctorId))
            ->where(function ($builder) use ($today) {
                $builder->where(function ($pendingQuery) use ($today) {
                    $pendingQuery->where('status', VacationStatus::PENDING)
                        ->whereDate('start_date', '<=', $today);
                })->orWhere(function ($approvedQuery) use ($today) {
                    $approvedQuery->where('status', VacationStatus::APPROVED)
                        ->whereDate('end_date', '<', $today);
                });
            });

        $vacations = $query->get();
        $changes = 0;

        foreach ($vacations as $vacation) {
            if ($vacation->status === VacationStatus::PENDING) {
                $vacation->status = VacationStatus::DECLINED;
                $reason = 'auto-declined because the start date was reached before approval';
            } elseif ($vacation->status === VacationStatus::APPROVED) {
                $vacation->status = VacationStatus::COMPLETED;
                $reason = 'completed automatically after the end date passed';
            } else {
                continue;
            }

            $vacation->save();
            $changes++;

            Log::info('Vacation status synced.', [
                'vacation_id' => $vacation->id,
                'doctor_id' => $vacation->doctor_id,
                'status' => $vacation->status->value,
                'reason' => $reason,
            ]);
        }

        return $changes;
    }

    public function isBlockingDate(int $doctorId, Carbon|string $date, bool $syncExpired = true): bool
    {
        if (! $this->vacationsTableExists()) {
            return false;
        }

        if ($syncExpired) {
            $this->syncExpiredVacations($doctorId);
        }

        return Vacation::query()
            ->where('doctor_id', $doctorId)
            ->blocking()
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->exists();
    }

    public function createVacation(int $doctorId, array $data, string $submittedBy, VacationStatus $status): Vacation
    {
        if (! $this->vacationsTableExists()) {
            throw ValidationException::withMessages([
                'vacations' => ['Vacation storage is not ready yet. Run the database migrations before creating vacations.'],
            ]);
        }

        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();
        $earliestAllowed = $this->earliestAllowedStartDate($doctorId);

        if ($startDate->lt($earliestAllowed)) {
            throw ValidationException::withMessages([
                'start_date' => ['Vacation start date must be at least 2 weeks after the doctor\'s last appointment day.'],
            ]);
        }

        return Vacation::create([
            'doctor_id' => $doctorId,
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
            'status' => $status,
            'submitted_by' => $submittedBy,
        ]);
    }

    public function approveVacation(Vacation $vacation): Vacation
    {
        $this->syncExpiredVacations($vacation->doctor_id);
        $vacation->refresh();

        if ($vacation->status !== VacationStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Only pending vacations can be approved.'],
            ]);
        }

        $vacation->status = VacationStatus::APPROVED;
        $vacation->save();

        Log::info('Vacation approved by admin.', [
            'vacation_id' => $vacation->id,
            'doctor_id' => $vacation->doctor_id,
        ]);

        return $vacation;
    }

    public function declineVacation(Vacation $vacation): Vacation
    {
        $this->syncExpiredVacations($vacation->doctor_id);
        $vacation->refresh();

        if ($vacation->status !== VacationStatus::PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Only pending vacations can be declined.'],
            ]);
        }

        $vacation->status = VacationStatus::DECLINED;
        $vacation->save();

        Log::info('Vacation declined by admin.', [
            'vacation_id' => $vacation->id,
            'doctor_id' => $vacation->doctor_id,
        ]);

        return $vacation;
    }

    public function dropVacation(Vacation $vacation): Vacation
    {
        $this->syncExpiredVacations($vacation->doctor_id);
        $vacation->refresh();

        if ($vacation->status !== VacationStatus::APPROVED) {
            throw ValidationException::withMessages([
                'status' => ['Only approved vacations can be dropped.'],
            ]);
        }

        $vacation->status = VacationStatus::DROPPED;
        $vacation->save();

        Log::info('Vacation dropped by admin.', [
            'vacation_id' => $vacation->id,
            'doctor_id' => $vacation->doctor_id,
            'booking_blocking' => false,
        ]);

        return $vacation;
    }
}