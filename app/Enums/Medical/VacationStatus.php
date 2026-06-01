<?php

namespace App\Enums\Medical;

enum VacationStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case DECLINED = 'declined';
    case COMPLETED = 'completed';
    case DROPPED = 'dropped';
}