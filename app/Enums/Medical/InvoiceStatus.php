<?php

namespace App\Enums\Medical;

enum InvoiceStatus: string
{
    case PAID = 'paid';
    case CANCELED = 'canceled';
    case UNPAID = 'unpaid';
}
