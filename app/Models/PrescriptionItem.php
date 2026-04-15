<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionItem extends Model
{
    protected $fillable = [
        'consultation_id',
        'medicine_name',
        'dosage',
        'frequency',
        'duration',
        'notes',
    ];

    public function consultation():BelongsTo {
        return $this->belongsTo(Consultation::class);
    }
}
