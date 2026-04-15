<?php

namespace App\Exceptions;

use App\Traits\ApiResponses;
use Exception;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class BusinessLogicException extends Exception
{
    use ApiResponses;

   public function render(Request $request): JsonResponse
    {
        return $this->error($this->getMessage(), 400);
    }
}
