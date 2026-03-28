<?php

namespace App\Enums\Medical;

enum ScheduleStatus: string 
{
    case ACTIVE = 'active';       
    case INACTIVE = 'inactive';   
    case ON_LEAVE = 'on_leave';   
}
