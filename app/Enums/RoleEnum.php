<?php

namespace App\Enums;

enum RoleEnum: string
{
    case ADMIN = 'admin';
    case DOCTOR = 'doctor';
    case PATIENT = 'patient';
}
