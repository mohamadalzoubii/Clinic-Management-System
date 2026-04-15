<?php

namespace App\DTOs\Chat;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageData
{
    public function __construct(
        public readonly int $receiverId,
        public readonly string $body
    ) {}

    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            receiverId: $request->validated('receiverId'),
            body: $request->validated('body'),
        );
    }
}
