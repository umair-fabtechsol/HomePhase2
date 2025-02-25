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
        Schema::create('delivery_images', function (Blueprint $table) {
            $table->id();
            $table->Integer('order_id');
            $table->string('type');
            $table->text('comments')->nullable();
            $table->text('before_images')->nullable();
            $table->text('after_images')->nullable();
            $table->datetime('Schedule_date_time')->nullable();                                            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('delivery_images');
    }
};