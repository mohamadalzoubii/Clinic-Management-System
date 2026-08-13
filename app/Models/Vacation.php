<?php

namespace App\Models;

use App\Enums\Medical\VacationStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Vacation extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'start_date',
        'end_date',
        'status',
        'submitted_by',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class)->withTrashed();
    }

    public function scopeBlocking(Builder $query)
    {
        return $query->whereIn('status', [
            VacationStatus::PENDING,
            VacationStatus::APPROVED,
        ]);
    }

    public function scopePending(Builder $query)
    {
        return $query->where('status', VacationStatus::PENDING);
    }

    public function scopeApproved(Builder $query)
    {
        return $query->where('status', VacationStatus::APPROVED);
    }

    public function overlapsDate(Carbon|string $date): bool
    {
        $date = Carbon::parse($date)->startOfDay();

        return Carbon::parse($this->start_date)->startOfDay()->lte($date)
            && Carbon::parse($this->end_date)->endOfDay()->gte($date);
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => VacationStatus::class,
        ];
    }
}