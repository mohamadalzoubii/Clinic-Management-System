<?php

namespace App\Services\Admin;

use App\Enums\Medical\AppointmentStatus;
use App\Enums\Medical\InvoiceStatus;
use App\Models\Admin;
use App\Models\Doctor;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\Patient;

class DashboardService
{
    public function overview(): array
    {
        $clinicUser = $this->resolveClinicUser();

        $doctors = Doctor::query()
            ->with('user')
            ->withCount('appointments')
            ->withAvg('reviews as rating_average', 'rating')
            ->latest()
            ->limit(5)
            ->get();

        $patients = Patient::query()
            ->with('user')
            ->withCount(['appointments as appointments_count' => function ($query) {
                $query->where('status', '!=', AppointmentStatus::CANCELLED->value);
            }])
            ->orderByDesc('appointments_count')
            ->latest()
            ->limit(5)
            ->get();

        $packages = Package::query()
            ->latest()
            ->limit(5)
            ->get();

        $revenue = (float) Invoice::query()
            ->where('user_id', $clinicUser->id)
            ->where('status', InvoiceStatus::PAYOUT_COMPLETED)
            ->sum('amount');

        return [
            'totals' => [
                'doctors' => Doctor::query()->count(),
                'patients' => Patient::query()->count(),
                'packages' => Package::query()->count(),
            ],
            'revenue' => round($revenue, 2),
            'focus' => [
                'top_patient_appointments' => $patients->first()?->appointments_count ?? 0,
                'highest_doctor_rating' => number_format((float) ($doctors->first()?->rating_average ?? 0), 1, '.', ''),
            ],
            'recent_doctors' => $doctors,
            'top_patients' => $patients,
            'packages' => $packages,
        ];
    }

    private function resolveClinicUser()
    {
        return Admin::query()
            ->with('user')
            ->firstOrFail()
            ->user;
    }
}