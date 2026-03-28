<?php

namespace App\Models;

use App\Enums\OtpType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OtpCode extends Model
{
    protected $fillable = [
        'email',
        'code',
        'type',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'type' => OtpType::class,
        ];
    }

    public function scopeForEmailAndType(Builder $query, string $email, OtpType $type): void
    {
        $query->where('email', $email)
              ->where('type', $type->value);
    }

    public function scopeValid(Builder $query): void {
        $query->where('expires_at','>',now());
    }

}
