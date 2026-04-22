<?php

namespace App\Http\Filters\V1;

class DoctorFilter extends QueryFilter
{
    public function specialization($value)
    {
        $specializations = explode(',', $value);

        return $this->builder->where(function ($query) use ($specializations) {
            foreach ($specializations as $spec) {
                $formattedSpec = str_replace('*', '%', trim($spec));
                $query->orWhere('specialization', 'like', $formattedSpec);
            }
        });
    }

    public function search($value)
    {
        $this->builder->wherehas('user', function ($query) use ($value) {
            $query->where('first_name', 'LIKE', '%'.$value.'%')
                ->orWhere('last_name', 'LIKE', '%'.$value.'%');
        });
    }

    public function gender($value)
    {
        $this->builder->where('gender', $value);
    }
}
