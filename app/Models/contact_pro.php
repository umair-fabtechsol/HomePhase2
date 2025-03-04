<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class contact_pro extends Model
{
    protected $fillable = [
        'customer_id',
        'provider_id',
        'deal_id',
        'subject',
        'text',
        'type',
        'read',
        'by_service',
    ];
}
