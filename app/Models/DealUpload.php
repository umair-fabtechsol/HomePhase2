<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DealUpload extends Model
{
    protected $fillable = [
        'deal_id',
        'images',
        'videos',
    ];
}