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
        $this->builder->whereHas('user', function ($query) use ($value) {
            $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$value}%"])
                ->orWhere('first_name', 'LIKE', "%{$value}%")
                ->orWhere('last_name', 'LIKE', "%{$value}%");
        });
    }

    public function gender($value)
    {
        $this->builder->where('gender', $value);
    }

    public function status($value)
    {
        $this->builder->whereHas('user', function ($query) use ($value) {
            $query->where('user_status', $value);
        });
    }

    public function wallet_balance($value)
    {
        $this->builder->whereHas('user', function ($query) use ($value) {
            $query->where('wallet_balance', '>=', $value);
        });
    }

    public function session_price($value)
    {
        $this->builder->where('session_price', $value);
    }

    public function years_of_experience($value)
    {
        $this->builder->where('years_of_experience', '<=', $value);
    }
}
