<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MedicalAttachment extends Model
{
    protected $fillable = [
        'file_path', 'file_name', 'mime_type',
    ];

    public function attchable(): MorphTo
    {
        return $this->morphTo();
    }
}
