<?php

namespace App\Enums;

enum OtpType: string
{
    case VERIFICATION = 'verification';       
    case PASSWORD_RESET = 'password_reset';   
    case EMAIL_UPDATE = 'email_update';       
}
