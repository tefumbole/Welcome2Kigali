<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInstitutionalMessages extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('institutional_message_counters')) {
            Schema::create('institutional_message_counters', function (Blueprint $table) {
                $table->increments('id');
                $table->string('type', 16);
                $table->string('year', 4);
                $table->unsignedInteger('next_number')->default(1);
                $table->timestamps();
                $table->unique(['type', 'year']);
            });
        }

        if (! Schema::hasTable('contact_messages')) {
            Schema::create('contact_messages', function (Blueprint $table) {
                $table->increments('id');
                $table->string('serial', 64)->unique();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone', 40)->nullable();
                $table->string('subject');
                $table->text('message');
                $table->string('ip', 45)->nullable();
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('contact_messages');
        Schema::dropIfExists('institutional_message_counters');
    }
}
