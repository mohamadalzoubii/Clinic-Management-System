<?php

namespace App\Http\Controllers\Api\V1\Secretary;

use App\Enums\Medical\AppointmentStatus;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Traits\ApiResponses;
use Carbon\Carbon;

class AppointmentController extends Controller
{
    use ApiResponses;

    public function today()
    {
        $today = Carbon::now()->toDateString();

        $count = Appointment::query()
            ->whereDate('appointment_date', $today)
            ->where('status', '!=', AppointmentStatus::CANCELLED->value)
            ->count();

        return $this->ok('Appointments today retrieved successfully.', [
            'count' => $count,
        ]);
    }
}
