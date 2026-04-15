<?php

namespace App\Actions\Medical\Stats;

use App\DTOs\Stats\DoctorDashboardData;
use App\Models\Appointment;

class GetDoctorDashboardStatsAction
{
    public function execute(int $doctorId)
    {
        $todayCount = Appointment::where('doctor_id', $doctorId)->today()->count();

        $pendingCount = Appointment::where('doctor_id', $doctorId)->pending()->count();

        $completedTodayCount = Appointment::where('doctor_id', $doctorId)->today()->completed()->count();

        return new DoctorDashboardData(
            $todayCount,
            $pendingCount,
            $completedTodayCount
        );
    }
}
