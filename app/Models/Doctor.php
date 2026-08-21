<?php

namespace App\Models;

use App\Enums\Medical\DoctorSpecialization;
use App\Traits\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Doctor extends Model implements HasMedia
{
    use Filterable, HasFactory, InteractsWithMedia, SoftDeletes;

    public $fillable = [
        'user_id',
        'specialization',
        'bio',
        'education',
        'certification',
        'years_of_experience',
        'session_price',
        'license_number',
        'gender',
    ];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('doctor_photo')
            ->singleFile();
    }

    public function getDoctorPhotoUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('doctor_photo');

        return $media?->getUrl();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function vacations(): HasMany
    {
        return $this->hasMany(Vacation::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(DoctorSchedule::class);
    }

    public function scheduleVersions(): HasMany
    {
        return $this->hasMany(DoctorScheduleVersion::class);
    }

    public function toEmbeddingText(): string
    {
        $parts = [
            'Specialization: '.$this->specialization->value,
            'Bio: '.$this->bio,
            'Years of experience: '.$this->years_of_experience,
        ];

        $reviewCount = $this->reviews()->count();

        if ($reviewCount > 0) {
            $averageRating = round((float) $this->reviews()->avg('rating'), 1);
            $parts[] = "Patient reviews: {$reviewCount} reviews, average rating {$averageRating} out of 5.";

            $recentComments = $this->reviews()
                ->latest()
                ->take(5)
                ->pluck('comment')
                ->filter()
                ->implode(' | ');

            if ($recentComments !== '') {
                $parts[] = "Recent patient feedback: {$recentComments}";
            }
        }

        return implode("\n", $parts);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(DoctorReview::class);
    }

    protected function casts(): array
    {
        return [
            'specialization' => DoctorSpecialization::class,
            'last_message_time' => 'datetime',
            'embedding' => 'array',
        ];
    }
}
