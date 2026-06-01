<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DoctorScheduleVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'effective_from_date',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'effective_from_date' => 'date',
        ];
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_admin_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DoctorScheduleVersionItem::class);
    }

    public function scopeForDoctor(Builder $query, int $doctorId): Builder
    {
        return $query->where('doctor_id', $doctorId);
    }
}