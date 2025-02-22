<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    protected $fillable = [
        'user_id',
        'service_id',
        'price_plan',
        'description',
        'price',
        'shedule',
    ];
}