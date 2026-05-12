<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    protected $fillable = [
        'appointment_id',
        'doctor_id',
        'patient_id',
        'anamnesis',
        'symptoms',
        'diagnosis',
        'next_visit_date',

    ];

    protected $casts = [
        'symptoms' => 'array',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function prescriptionItems(): HasMany
    {
        return $this->hasMany(prescriptionItem::class);
    }

    public function scopeWithFullDetails($query)
    {
        return $query->with(['prescriptionItems', 'appointment.attachments'])->latest();
    }
}
