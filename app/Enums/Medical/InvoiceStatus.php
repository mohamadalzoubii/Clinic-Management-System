<?php

namespace App\Enums\Medical;

enum InvoiceStatus: string
{
    case PAID = 'paid';
    case CANCELED = 'canceled';
    case UNPAID = 'unpaid';
    case REFUNDED = 'refunded';
    case PAYOUT_COMPLETED = 'payout_completed';
}
