<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWaMessageSerialsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('wa_message_serials')) {
            Schema::create('wa_message_serials', function (Blueprint $table) {
                $table->increments('id');
                $table->string('serial', 64)->unique();
                $table->string('kind', 40)->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('wa_message_serial_counters')) {
            Schema::create('wa_message_serial_counters', function (Blueprint $table) {
                $table->string('year', 2)->primary();
                $table->unsignedInteger('next_number')->default(1);
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('wa_message_serials');
        Schema::dropIfExists('wa_message_serial_counters');
    }
}
