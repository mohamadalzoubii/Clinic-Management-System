<?php

namespace App\DTOs\Stats;

class DoctorDashboardData
{
    public function __construct(
        public readonly int $todayAppointments,
        public readonly int $pendingAppointments,
        public readonly int $completedToday
    ) {}

}
