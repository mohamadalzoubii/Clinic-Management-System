<?php

namespace App\Models;

use App\Enums\Medical\InvoiceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = [
        'appointment_id',
        'user_id',
        'amount',
        'invoice_number',
        'entry_type',
        'status',
        'paid_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    protected function casts(): array
    {
        return [
            'status' => InvoiceStatus::class,
            'paid_at' => 'datetime',
            'created_at' => 'datetime',
        ];
    }
}
