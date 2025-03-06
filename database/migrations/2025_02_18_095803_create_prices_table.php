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
        Schema::create('prices', function (Blueprint $table) {
            $table->id();
            $table->Integer('user_id');
            $table->bigInteger('call_pro')->nullable();
            $table->bigInteger('text_pro')->nullable();
            $table->bigInteger('instant_chat')->nullable();
            $table->bigInteger('email_pro')->nullable();
            $table->bigInteger('get_direction')->nullable();
            $table->bigInteger('th_call_pro')->nullable();
            $table->bigInteger('th_text_pro')->nullable();
            $table->bigInteger('th_instant_chat')->nullable();
            $table->bigInteger('th_email_pro')->nullable();
            $table->bigInteger('th_get_direction')->nullable();
            $table->bigInteger('customer_call_commission')->nullable();
            $table->bigInteger('customer_text_commission')->nullable();
            $table->bigInteger('customer_chat_commission')->nullable();
            $table->bigInteger('customer_email_commission')->nullable();
            $table->bigInteger('customer_transaction_commission')->nullable();
            $table->bigInteger('customer_service_fee')->nullable();
            $table->bigInteger('provider_service_fee')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prices');
    }
};