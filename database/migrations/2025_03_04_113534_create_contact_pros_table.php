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
        Schema::create('contact_pros', function (Blueprint $table) {
            $table->id();
            $table->integer('customer_id');
            $table->integer('provider_id');
            $table->integer('deal_id')->nullable();
            $table->string('subject')->nullable();
            $table->text('text')->nullable();
            $table->enum('type', ['call_pro', 'sms_pro', 'email_pro']);
            $table->boolean('read')->default(false);
            $table->boolean('by_service')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_pros');
    }
};
