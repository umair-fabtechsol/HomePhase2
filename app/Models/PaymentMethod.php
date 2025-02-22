<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    //add payment methods
    protected $fillable = [
        'user_id',
        'method_type',
        'card_name',
        'card_number',
        'card_cvv',
        'card_expiry',
    ];
}
