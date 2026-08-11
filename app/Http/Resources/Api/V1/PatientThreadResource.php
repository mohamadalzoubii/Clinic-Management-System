<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PatientThreadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $patientName = $this->user
            ? trim($this->user->first_name . ' ' . $this->user->last_name)
            : 'Unknown Patient';

        return [
            'patient_id' => $this->id,
            'patient_name' => $patientName ?: 'Unknown Patient',
            'last_message' => $this->last_message ?: 'No messages',
            'last_message_time' => $this->last_message_time ? \Carbon\Carbon::parse($this->last_message_time)->setTimezone('Asia/Damascus')->format('Y-m-d h:i A') : null,
            'unread_count' => (int) ($this->unread_count ?? 0),
            'patient_email' => $this->user?->email,
        ];
    }
}

