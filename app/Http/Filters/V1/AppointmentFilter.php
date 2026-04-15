<?php

namespace App\Http\Filters\V1;

class AppointmentFilter extends QueryFilter
{
    protected $sortable = ['status', 'date'];

    public function status($value)
    {

        $normalizedValue = $this->normalize($value);

        $this->builder->whereIn('status', $normalizedValue);
    }

    public function date($value)
    {
        $dates = explode(',', $value);

        if (count($dates) > 1) {
            return $this->builder->whereBetween('appointment_date', $dates);
        }

        return $this->builder->whereDate('appointment_date', $value);
    }
}
