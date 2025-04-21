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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('lastname')->nullable();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('phone')->nullable();
            $table->integer('role');
            $table->string('password');
            $table->integer('terms')->nullable();
          
            $table->string('personal_image')->nullable();
            $table->string('sales_referred')->nullable();
            $table->integer('sales_representative')->nullable();
            $table->string('referral_type')->nullable();


            $table->text('location')->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('client_permission_1')->nullable();
            $table->integer('client_permission_2')->nullable();
            $table->integer('client_permission_3')->nullable();
            $table->integer('assign_permission_1')->nullable();
            $table->integer('assign_permission_2')->nullable();
            $table->integer('assign_permission_3')->nullable();

            $table->integer('general_notification')->nullable();
            $table->integer('provider_notification')->nullable();
            $table->boolean('customer_notification')->default(true);
            $table->integer('sales_notification')->nullable();
            $table->integer('message_notification')->nullable();
            $table->string('_id')->nullable();
            $table->integer('status')->default(0);
            $table->integer('assign_sales_rep')->nullable();
            $table->string('stripe_account_id')->nullable();
            $table->text('token')->nullable();
            $table->integer('firsttimeLogin')->default(1);
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};