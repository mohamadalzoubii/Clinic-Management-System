<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    public $fillable = [
        'user_id',
        'specialization',
        'bio',
        'education',
        'certification',
        'years_of_experience',
        'license_number',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function shedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }
}
