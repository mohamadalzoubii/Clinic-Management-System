<?php

use App\Models\Appointment;
use App\Notifications\AppointmentReminder;
use Carbon\Carbon;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function () {

    $targetTime = Carbon::now()->addDay();

    $appointments = Appointment::whereBetween('appointment_at', [
        $targetTime->copy()->subMinutes(30),
        $targetTime->copy()->addMinutes(30),
    ])
        ->where('reminder_sent', false)
        ->get();

    foreach ($appointments as $appointment) {
        $appointment->patient->user->notify(new AppointmentReminder($appointment));


        $appointment->update(['reminder_sent' => true]);
    }
})->hourly();

Schedule::command('appointments:auto-cancel-expired-agenda')->dailyAt('00:05');
