<?php

namespace App\Http\Filters\V1;

class DoctorFilter extends QueryFilter
{
    public function specialization($value)
    {
        $likeStr = str_replace('*', '%', $value);

        return $this->builder->where('specialization', 'like', $likeStr);
    }

    public function search($value)
    {
        $this->builder->wherehas('user', function ($query) use ($value) {
            $query->where('first_name', 'LIKE', '%'.$value.'%')
                ->orWhere('last_name', 'LIKE', '%'.$value.'%');
        });
    }
}
