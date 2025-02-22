<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
    protected $fillable = [
        'user_id',
        'call_pro',
        'text_pro',
        'instant_chat',
        'email_pro',
        'get_direction',
        'referral_commission',
        'transection_fee',
        'customer_service_fee',
        'provider_service_fee',
    ];
}
