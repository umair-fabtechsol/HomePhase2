<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Hour extends Model
{
    protected $fillable = [
        'business_id',
        'regular_hour',
        'special_hour',
        'day_name',
        'day_status',
        'start_time',
        'end_time',
        'special_start_time',
        'special_end_time',
    ];
}
