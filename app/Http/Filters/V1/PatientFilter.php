<?php

namespace App\Http\Filters\V1;

class PatientFilter extends QueryFilter
{
    public function search($value)
    {
        $this->builder->whereHas('user', function ($query) use ($value) {
            $query->where('first_name', 'LIKE', '%'.$value.'%')
                ->orWhere('last_name', 'LIKE', '%'.$value.'%')
                ->orWhere('email', 'LIKE', '%'.$value.'%');
        });
    }

    public function status($value)
    {
        $this->builder->whereHas('user', function ($query) use ($value) {
            $query->where('user_status', $value);
        });
    }
}