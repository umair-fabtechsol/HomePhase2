<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Deal extends Model
{
    protected $fillable = [
        'user_id',
        'service_title',
        'commercial',
        'residential',
        'service_category',
        'search_tags',
        'service_description',
        'fine_print',
        'pricing_model',
        'flat_rate_price',
        'flat_by_now_discount',
        'flat_final_list_price',
        'flat_estimated_service_time',
        'hourly_rate',
        'discount',
        'hourly_final_list_price',
        'hourly_estimated_service_time',
        'title1',
        'deliverable1',
        'price1',
        'by_now_discount1',
        'final_list_price1',
        'estimated_service_timing1',
        'title2',
        'deliverable2',
        'price2',
        'by_now_discount2',
        'final_list_price2',
        'estimated_service_timing2',
        'title3',
        'deliverable3',
        'price3',
        'by_now_discount3',
        'final_list_price3',
        'estimated_service_timing3',
        'images',
        'videos',
        'publish',
    ];
}