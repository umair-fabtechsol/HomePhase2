<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'provider_id',
        'deal_id',
        'rating',
        'desciption',
    ];
}
