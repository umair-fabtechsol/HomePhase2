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
        'customer_call_commission',
        'customer_text_commission',
        'customer_chat_commission',
        'customer_email_commission',
        'customer_transaction_commission',
        'customer_service_fee',
        'provider_service_fee',


        
    ];
}