<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
    protected $fillable = [
        'user_id',
        'service_title',
        'bank',
        'branch_name',
        'account_number',
        'bank_routing_number',



    ];
}
