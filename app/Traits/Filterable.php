<?php

namespace App\Traits;

use App\Http\Filters\V1\QueryFilter;
use Illuminate\Database\Eloquent\Builder;

trait Filterable
{
    public function scopeFilter(Builder $query, QueryFilter $filter)
    {
        return $filter->apply($query);
    }
}
