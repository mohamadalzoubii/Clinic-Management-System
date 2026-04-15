<?php

namespace App\DTOs\Chat;

class GetMessagesData
{
    public function __construct(public readonly int $receiverId) {}
}
