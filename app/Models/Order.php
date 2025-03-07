<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'provider_id',
        'deal_id',
        'total_amount',
        'status',
        'notes',
        'scheduleDate',
        'date',
        
    ];
}