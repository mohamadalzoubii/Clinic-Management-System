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

    public function Doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function scopeExistsFor(Builder $query, int $doctorId, int $patientId)
    {
        return $query->where('doctor_id', $doctorId)
            ->where('patient_id', $patientId);
    }
}
