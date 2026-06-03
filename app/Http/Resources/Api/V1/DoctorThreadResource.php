<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $doctorName = $this->user 
            ? trim($this->user->first_name . ' ' . $this->user->last_name) 
            : 'Unknown Doctor';

        return [
            'doctor_id'         => $this->id,
            'doctor_name'       => $doctorName ?: 'Unknown Doctor',
            'doctor_image'      => $this->doctor_photo_url, 
            'last_message'      => $this->last_message ?: 'No messages',
            'last_message_time' => $this->last_message_time ? $this->last_message_time : null,
            'unread_count'      => (int) ($this->unread_count ?? 0),
        ];
    }
}