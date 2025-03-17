<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('business_name')->nullable();
            $table->string('business_logo')->nullable();
            $table->string('location')->nullable();
            $table->text('about')->nullable();
            $table->string('business_primary_category')->nullable();
            $table->text('business_secondary_categories')->nullable();
            $table->string('website')->nullable();
        
            $table->binary('about_video')->nullable();
            $table->text('technician_photo')->nullable();
            $table->text('vehicle_photo')->nullable();
            $table->text('facility_photo')->nullable();
            $table->text('project_photo')->nullable();

            $table->text('insurance_certificate')->nullable();
            $table->text('license_certificate')->nullable();
            $table->text('award_certificate')->nullable();
            $table->text('regular_hour')->nullable();
            $table->text('special_hour')->nullable();
            $table->text('conversation_call_number')->nullable();
            $table->text('conversation_text_number')->nullable();
            $table->boolean('conversation_chat')->default(false);
            $table->text('conversation_email')->nullable();
            $table->text('conversation_address')->nullable();
            $table->string('service_location_type')->nullable();
            $table->text('business_location')->nullable();
            $table->text('service_location')->nullable();
            $table->text('restrict_location')->nullable();
            $table->text('location_miles')->nullable();
            $table->integer('service_bulk')->nullable();
    
            $table->integer('publish')->nullable();

            $table->string('postalCode')->nullable();
            $table->string('city')->nullable();
            $table->string('longitude')->nullable();
            $table->string('latitude')->nullable();
            $table->string('place')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};