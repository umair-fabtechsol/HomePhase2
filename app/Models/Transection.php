<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transection extends Model
{
    protected $fillable = [
        'type', 
        'payer_id', 
        'payer_role', 
        'order_id', 
        'provider_id',
        'customer_id', 
        'referral_id', 
        'stripe_charge_id',
        'stripe_transfer_id',
        'amount',
        'currency',
        'admin_balance',
        'provider_deduction',
        'provider_balance',
        'customer_deduction',
        'referral_balance',
        'customer_payment_status',
        'provider_payment_status',
        'provider_payout_status',
        'referral_payout_status',
    ];

}
