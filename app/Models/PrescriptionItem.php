<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'consultation_id',
        'medicine_name',
        'category',
        'dosage',
        'form_and_quantity',
        'frequency',
        'duration',
        'special_instructions',
        'storage_instructions',
        'side_effects',
        'allergy_warnings',
    ];

    public function consultation(): BelongsTo
    {
        return $this->belongsTo(Consultation::class);
    }
}
