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
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('service_title')->nullable();
            $table->string('commercial')->nullable();
            $table->string('residential')->nullable();
            $table->string('service_category')->nullable();
            $table->text('search_tags')->nullable();
            $table->text('service_description')->nullable();
            $table->text('fine_print')->nullable();

            $table->string('pricing_model')->nullable();
            // ============
            $table->string('flat_rate_price')->nullable();
            $table->string('flat_by_now_discount')->nullable();
            $table->string('flat_final_list_price')->nullable();
            $table->string('flat_estimated_service_time')->nullable();
            // ================== 
            $table->string('hourly_rate')->nullable();
            $table->string('discount')->nullable();
            $table->string('hourly_final_list_price')->nullable();
            $table->string('hourly_estimated_service_time')->nullable();
            // ==================== 
            $table->string('title1')->nullable();
            $table->text('deliverable1')->nullable();
            $table->string('price1')->nullable();
            $table->string('by_now_discount1')->nullable();
            $table->string('final_list_price1')->nullable();
            $table->string('estimated_service_timing1')->nullable();

            $table->string('title2')->nullable();
            $table->text('deliverable2')->nullable();
            $table->string('price2')->nullable();
            $table->string('by_now_discount2')->nullable();
            $table->string('final_list_price2')->nullable();
            $table->string('estimated_service_timing2')->nullable();

            $table->string('title3')->nullable();
            $table->text('deliverable3')->nullable();
            $table->string('price3')->nullable();
            $table->string('by_now_discount3')->nullable();
            $table->string('final_list_price3')->nullable();
            $table->string('estimated_service_timing3')->nullable();

            $table->binary('images')->nullable();
            $table->binary('videos')->nullable();


            $table->integer('publish')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};