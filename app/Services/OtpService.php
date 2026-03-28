<?php

namespace App\Services;

use app\Enums\OtpType;
use App\Mail\SendOtpMail;
use App\Models\OtpCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class OtpService
{
public function generateAndSend(string $email, OtpType $type) {
    
    $otp = random_int(100000, 999999);

    OtpCode::forEmailAndType($email, $type)->delete();

        OtpCode::create([
            'email'      => $email,
            'code'       => Hash::make($otp), 
            'type'       => $type->value,
            'expires_at' => now()->addMinutes(15),
        ]);

        Mail::to($email)->queue(new SendOtpMail($otp));
}
}
