<?php

namespace App\Actions\Appointment;

use App\DTOs\Appointment\IndexAppointmentData;
use App\Http\Filters\V1\AppointmentFilter;
use App\Models\Appointment;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GetAppointmentsAction
{
    public function __construct(
        private readonly AppointmentFilter $filter,
    ) {}

    public function execute(IndexAppointmentData $dto): LengthAwarePaginator
    {
        $query = Appointment::query()
            ->with(['patient.user', 'doctor.user']);

        $this->filter->apply($query);

        if (empty($query->getQuery()->orders)) {
            $query->latest();
        }

        return $query->paginate($dto->perPage);
    }
}
