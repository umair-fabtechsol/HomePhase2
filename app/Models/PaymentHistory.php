<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentHistory extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'payment_method',
        'amount',
        'payment_type',
        'status',
    ];
}