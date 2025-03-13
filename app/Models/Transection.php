<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transection extends Model
{
    protected $fillable = [
        'user_id', 
        'user_role', 
        'stripe_charge_id',
        'stripe_transfer_id',
        'amount',
        'type',
        'currency',
        'status'
    ];

}
