<?php

namespace App\Actions\Admin;

use App\DTOs\Admin\ScheduleIndexData;
use App\Models\DoctorSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetDoctorSchedulesAction
{
    public function execute(ScheduleIndexData $data): LengthAwarePaginator
    {
        return DoctorSchedule::with(['doctor.user'])
            ->when($data->doctorId && $data->doctorId !== 'all', fn ($query) => $query->where('doctor_id', $data->doctorId))
            ->when($data->status, fn ($query) => $query->where('status', $data->status))
            ->when($data->search, fn ($query) => $query->whereHas('doctor.user', function ($userQuery) use ($data) {
                $userQuery->where('first_name', 'like', '%'.$data->search.'%')
                    ->orWhere('last_name', 'like', '%'.$data->search.'%')
                    ->orWhere('email', 'like', '%'.$data->search.'%');
            }))
            ->latest()
            ->paginate(10);
    }
}
