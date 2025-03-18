<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transection extends Model
{
    protected $fillable = [
        'payer_id', 
        'payer_role', 
        'providers_cus_id', 
        'stripe_charge_id',
        'stripe_transfer_id',
        'amount',
        'type',
        'currency',
        'status'
    ];

}
