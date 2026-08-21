<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppointmentChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public int $doctorId,
        public int $appointmentId,
        public string $changeType,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('doctor.'.$this->doctorId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'appointment.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'appointment_id' => $this->appointmentId,
            'doctor_id' => $this->doctorId,
            'change_type' => $this->changeType,
        ];
    }
}