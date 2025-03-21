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
        Schema::create('transections', function (Blueprint $table) {
            $table->id();
            $table->string('type'); 
            $table->integer('payer_id'); 
            $table->integer('payer_role'); 
            $table->integer('provider_id')->nullable();
            $table->integer('customer_id')->nullable();
            $table->integer('referral_id')->nullable();
            $table->integer('order_id')->nullable();
            $table->string('stripe_charge_id')->nullable();
            $table->string('stripe_transfer_id')->nullable();
            $table->integer('amount');
            $table->string('currency', 10)->default('usd');
            $table->integer('admin_balance')->nullable();
            $table->integer('provider_deduction')->nullable();
            $table->integer('provider_balance')->nullable();
            $table->integer('customer_deduction')->nullable();
            $table->integer('referral_balance')->nullable();
            $table->string('customer_payment_status')->default('pending');
            $table->string('provider_payment_status')->default('pending');
            $table->string('provider_payout_status')->default('pending');
            $table->string('referral_payout_status')->default('pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transections');
    }
};
