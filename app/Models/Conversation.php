<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    protected $fillable = ['patient_id', 'doctor_id'];

    public function doctor(): belongsTo
    {
        return $this->beloongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function scopeBetween(Builder $query, int $patientId, int $doctorId): Builder
    {
        return $query->where('patient_id', $patientId)
            ->where('doctor_id', $doctorId);
    }
}
