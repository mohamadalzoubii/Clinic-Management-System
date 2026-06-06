<?php

namespace App\Models;

use App\Enums\Medical\AppointmentStatus;
use App\Traits\Filterable;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Appointment extends Model
{
    use Filterable;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'appointment_date',
        'start_time',
        'end_time',
        'status',
        'reason',
        'notes',
        'reminder_sent',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function consultation(): HasOne
    {
        return $this->hasOne(Consultation::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(MedicalAttachment::class, 'attachable');
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class)->latestOfMany();
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function scopeBlockingTimeOnDate(Builder $query, Carbon|string $date)
    {
        return $query->whereDate('appointment_date', $date)
            ->whereIn('status', [
                AppointmentStatus::PENDING,
            ]);
    }

    public function scopeConflicting(Builder $query, Carbon|string $date, Carbon|string $time)
    {
        return $query->whereDate('appointment_date', $date)
            ->whereTime('start_time', $time)
            ->whereIn('status', [
                AppointmentStatus::PENDING,
            ]);
    }

    public function scopeHasCompletedVisit(Builder $query, int $patientId, int $doctorId)
    {
        return $query->where('patient_id', $patientId)
            ->where('doctor_id', $doctorId)
            ->whereIn('status', [AppointmentStatus::COMPLETED]);
    }

    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('appointment_date', Carbon::today());
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::PENDING->value);
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AppointmentStatus::COMPLETED->value);
    }

    public function isPending(): bool
    {
        return $this->status === AppointmentStatus::PENDING;
    }

    public function canBeCancelled(): bool
    {
        return $this->canBeCancelledByPatient();
    }

    public function canBeCancelledByPatient(): bool
    {
        $appointmentDateTime = $this->appointmentDateTime();

        return ! $appointmentDateTime->subHours(2)->isPast();
    }

    public function appointmentDateTime(): Carbon
    {
        return Carbon::parse(Carbon::parse($this->appointment_date)->format('Y-m-d').' '.$this->start_time);
    }

    public function scopeForDoctorAgenda($query, int $doctorId, string $startDate, string $endDate)
    {
        return $query->with(['patient.user'])
            ->where('doctor_id', $doctorId)
            ->whereBetween('appointment_date', [$startDate, $endDate])
            ->whereIn('status', [
                AppointmentStatus::PENDING->value,
                AppointmentStatus::COMPLETED->value,
                AppointmentStatus::NO_SHOW->value,
            ])
            ->orderBy('appointment_date')
            ->orderBy('start_time');
    }

    protected function casts()
    {
        return [
            'status' => AppointmentStatus::class,
            'appointment_date' => 'date',
        ];
    }
}
