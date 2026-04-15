<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'type' => 'message',
            'id' => (string) $this->id,

            'attributes' => [
                'sender_user_id' => $this->sender_user_id,
                'is_mine' => $this->sender_user_id === auth()->id(),
                'body' => $this->body,
                'is_read' => (bool) $this->is_read,
                'created_at' => $this->created_at->format('Y-m-d H:i:s'),
                'time_diff' => $this->created_at->diffForHumans(),
            ],

        ];
    }
}
