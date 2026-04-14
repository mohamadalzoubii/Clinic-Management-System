<?php

namespace App\Models;

use App\Enums\Medical\DoctorSpecialization;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Doctor extends Model
{
    use Filterable, HasFactory;

    public $fillable = [
        'user_id',
        'specialization',
        'bio',
        'education',
        'certification',
        'years_of_experience',
        'session_price',
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

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DoctorReview::class);
    }

    protected function casts(): array
    {
        return [
            'specialization' => DoctorSpecialization::class,
        ];
    }
}
