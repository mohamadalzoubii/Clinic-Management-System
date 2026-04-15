<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public $fillable = ['name', 'balance_amount', 'price'];
}
