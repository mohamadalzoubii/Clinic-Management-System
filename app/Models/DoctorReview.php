<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorReview extends Model
{
    public $fillable = [
        'doctor_id',
        'patient_id',
        'comment',
        'rating',
    ];

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function scopeExistsFor(Builder $query, int $patientId, int $doctorId)
    {
        return $query->where('patient_id', $patientId)
            ->where('doctor_id', $doctorId);
    }
}
