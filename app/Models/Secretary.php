<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Secretary extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'work_days',
        'monthly_salary',
    ];

    protected function casts(): array
    {
        return [
            'monthly_salary' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}