<?php

namespace App\Models;

use App\Enums\Medical\DayOfWeek;
use App\Enums\Medical\ScheduleStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class DoctorSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_duration',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'status' => ScheduleStatus::class,
            'slot_duration' => 'integer',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }


    public function scopeActiveOnDay(Builder $query, string $dayOfWeek)
    {
        return $query->where('day_of_week', $dayOfWeek)
            ->where('status', ScheduleStatus::ACTIVE);
    }
}
