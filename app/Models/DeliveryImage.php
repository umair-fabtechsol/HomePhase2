<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryImage extends Model
{
    protected $fillable = [
        'order_id',
        'type',
        'comments',
        'images',
    ];
}