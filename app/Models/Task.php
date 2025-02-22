<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = [
        'created_by',
        'name',
        'due_datetime',
        'files',
        'notification',
        'status',
        'description',
    ];
}