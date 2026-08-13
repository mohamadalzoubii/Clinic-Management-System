<?php

namespace App\Http\Filters\V1;

class AppointmentFilter extends QueryFilter
{
    protected $sortable = ['status', 'date' => 'appointment_date', 'created_at'];

    protected bool $partyFiltered = false;

    public function search($value)
    {
        $this->builder->where(function ($query) use ($value) {
            $query->whereHas('doctor.user', function ($userQuery) use ($value) {
                $userQuery->where('first_name', 'LIKE', '%'.$value.'%')
                    ->orWhere('last_name', 'LIKE', '%'.$value.'%');
            })->orWhereHas('patient.user', function ($userQuery) use ($value) {
                $userQuery->where('first_name', 'LIKE', '%'.$value.'%')
                    ->orWhere('last_name', 'LIKE', '%'.$value.'%');
            });
        });
    }

    public function party_type($value)
    {
        $this->applyPartyFilter();
    }

    public function party_id($value)
    {
        $this->applyPartyFilter();
    }

    protected function applyPartyFilter(): void
    {
        if ($this->partyFiltered) {
            return;
        }

        $partyType = $this->request->get('party_type');
        $partyId = $this->request->get('party_id');

        if ($partyType && $partyId) {
            if ($partyType === 'doctor') {
                $this->builder->where('doctor_id', $partyId);
                $this->partyFiltered = true;
            } elseif ($partyType === 'patient') {
                $this->builder->where('patient_id', $partyId);
                $this->partyFiltered = true;
            }
        }
    }

    public function doctor_id($value)
    {
        if ($value) {
            $this->builder->where('doctor_id', $value);
        }
    }

    public function patient_id($value)
    {
        if ($value) {
            $this->builder->where('patient_id', $value);
        }
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

    public function date($value)
    {
        $dates = $this->normalize($value);

        if (count($dates) > 1) {
            return $this->builder->whereBetween('appointment_date', $dates);
        }

        return $this->builder->whereDate('appointment_date', $dates[0]);
    }

    public function day($value)
    {
        return $this->date($value);
    }
}
