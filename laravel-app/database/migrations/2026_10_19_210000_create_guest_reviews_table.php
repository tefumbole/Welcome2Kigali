<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGuestReviewsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('guest_reviews')) {
            return;
        }

        Schema::create('guest_reviews', function (Blueprint $table) {
            $table->increments('id');
            $table->string('reference', 80)->nullable();
            $table->string('guest_name', 80)->nullable();
            $table->unsignedTinyInteger('cleanliness');
            $table->unsignedTinyInteger('on_time');
            $table->unsignedTinyInteger('service');
            $table->unsignedTinyInteger('food_quality');
            $table->text('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('guest_reviews');
    }
}
