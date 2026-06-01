<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\Admin\DashboardResource;
use App\Services\Admin\DashboardService;
use App\Traits\ApiResponses;

class DashboardController extends Controller
{
    use ApiResponses;

    public function index(DashboardService $service)
    {
        return $this->ok('Admin overview retrieved successfully.', (new DashboardResource($service->overview()))->resolve());
    }
}