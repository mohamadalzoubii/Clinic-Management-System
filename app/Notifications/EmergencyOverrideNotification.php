<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class EmergencyOverrideNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Appointment $appointment)
    {
        //
    }

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toBroadcast(object $notifiable)
    {
        return [
            'appointment_id' => $this->appointment->id,
            'title' => 'تم إلغاء موعدك',
            'body' => 'تم إلغاء موعدك بسبب حالة طارئة، وسيتم التواصل معك للتعويض قريباً.',
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'appointment_id' => $this->appointment->id,
            'title' => 'تم إلغاء موعدك',
            'body' => 'تم إلغاء موعدك بسبب حالة طارئة، وسيتم التواصل معك للتعويض قريباً.',
        ];
    }
}
