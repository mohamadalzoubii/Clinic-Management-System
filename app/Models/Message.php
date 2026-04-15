<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    protected $fillable = ['conversation_id', 'sender_user_id', 'body', 'is_read'];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_user_id');
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function scopeForConversation(Builder $query, int $conversationId)
    {
        return $query->where('conversation_id', $conversationId);
    }

    public function scopeUnreadFromOthers(Builder $query, int $userId)
    {
        return $query->where('sender_user_id', '!=', $userId)->where('is_read', false);
    }

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
        ];
    }
}
