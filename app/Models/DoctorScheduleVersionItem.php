<?php

namespace App\Models;

use App\Enums\Medical\DayOfWeek;
use App\Enums\Medical\ScheduleStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorScheduleVersionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_schedule_version_id',
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

    public function version(): BelongsTo
    {
        return $this->belongsTo(DoctorScheduleVersion::class, 'doctor_schedule_version_id');
    }
}