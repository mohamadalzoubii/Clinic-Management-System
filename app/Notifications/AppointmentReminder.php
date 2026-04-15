<?php

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Notification;

class AppointmentReminder extends Notification implements ShouldBroadcast
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Appointment $appointment)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toArray($notifiable)
    {
        return [
            'appointment' => $this->appointment->id,
            'message' => 'Reminder: Your medical appointment is scheduled for tomorrow at '.$this->appointment->appointment_at->format('H:i'),
            'time' => $this->appointment->appointment_at,
        ];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toBroadcast($notifiable): array
    {
        return [
            'appointment' => $this->appointment->id,
            'title' => 'Appointment reminder',
            'message' => 'Your reminder is after 24h from now',
        ];
    }
}
