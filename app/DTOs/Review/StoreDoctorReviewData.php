<?php

namespace App\DTOs\Review;

use Illuminate\Http\Request;

class StoreDoctorReviewData
{

    public function __construct(
        public readonly ?string $comment,
        public readonly int $rating
    ) {}

    public static function formRequest(Request $request):self {
        return new self(
            rating: $request->validated('rating'),
            comment: $request->validated('comment')
        );
    }
}
