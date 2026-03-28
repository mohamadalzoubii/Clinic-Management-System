<?php

namespace App\Enums\Medical;

enum AppointmentStatus: string 
{
    case PENDING = 'pending';       
    case CONFIRMED = 'confirmed';   
    case COMPLETED = 'completed';   
    case CANCELLED = 'cancelled';   
    case NO_SHOW = 'no_show';       
}
