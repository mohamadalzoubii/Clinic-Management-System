<?php

namespace App\Models;

use App\Enums\Medical\AppointmentStatus;
use App\Enums\Medical\BloodType;
use App\Enums\Medical\Gender;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;

class Patient extends Model implements HasMedia
{
    use Filterable, HasFactory, SoftDeletes, InteractsWithMedia;

    protected $fillable = [
        'user_id',
        'emergency_contact_name',
        'emergency_contact_phone',
        'emergency_contact_relationship',
        'emergency_contact_email',
        'emergency_contact_city',
        'allergies',
        'chronic_diseases',
        'weight',
        'height',
        'gender',
        'blood_type',
        'date_of_birth',
        'city',
        'smoking_status',
        'alcohol_status',
        'is_smoker',
        'drinks_alcohol'
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('xray_images');
        $this->addMediaCollection('medical_test_images');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeHasAppointmentWith(Builder $query, int $doctorId)
    {
        return $this->appointments()
            ->where('doctor_id', $doctorId)
            ->where('status', '!=', AppointmentStatus::CANCELLED)
            ->exists();
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    protected function casts(): array
    {
        return [
            'blood_type' => BloodType::class,
            'gender' => Gender::class,
            'weight' => 'float',
            'height' => 'float',
            'is_smoker' => 'boolean',
            'drinks_alcohol' => 'boolean',
            'date_of_birth' => 'date:Y-m-d',
        ];
    }
}
