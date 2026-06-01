<?php

namespace App\Http\Filters\V1;

class AppointmentFilter extends QueryFilter
{
    protected $sortable = ['status', 'date'];

    public function search($value)
    {
        $this->builder->where(function ($query) use ($value) {
            $query->whereHas('doctor.user', function ($userQuery) use ($value) {
                $userQuery->where('first_name', 'LIKE', '%'.$value.'%')
                    ->orWhere('last_name', 'LIKE', '%'.$value.'%');
            });
        });
    }

    public function specialization($value)
    {
        $specializations = $this->normalize($value);

        $this->builder->whereHas('doctor', function ($query) use ($specializations) {
            $query->where(function ($specializationQuery) use ($specializations) {
                foreach ($specializations as $specialization) {
                    $formattedSpecialization = str_replace('*', '%', trim($specialization));
                    $specializationQuery->orWhere('specialization', 'like', $formattedSpecialization);
                }
            });
        });
    }

    public function status($value)
    {
        $this->builder->whereIn('status', $this->normalize($value));
    }

    public function day($value)
    {
        $dates = $this->normalize($value);

        if (count($dates) > 1) {
            return $this->builder->whereBetween('appointment_date', $dates);
        }

        return $this->builder->whereDate('appointment_date', $dates[0]);
    }
}
