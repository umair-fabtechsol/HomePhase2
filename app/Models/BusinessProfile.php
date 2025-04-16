<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'business_logo',
        'location',
        'about',
        'business_primary_category',
        'business_secondary_categories',
        'website',
        'about_video',
        'technician_photo',
        'vehicle_photo',
        'facility_photo',
        'project_photo',
        'insurance_certificate',
        'license_certificate',
        'award_certificate',
        'regular_hour',
        'special_hour',
        'conversation_call_number',
        'conversation_text_number',
        'conversation_chat',
        'conversation_email',
        'conversation_address',
        'conversation_website',
        'service_location_type',
        'business_location',
        'service_location',
        'primary_location',
        'restrict_location',
        'location_miles',
        'service_bulk',
        'publish',
        'postalCode',
        'city',
        'longitude',
        'latitude',
        'place',
    ];
}