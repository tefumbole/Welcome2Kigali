<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnInBookings extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (! Schema::hasColumn('bookings', 'mtn_phone')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('mtn_phone')->nullable();
            });
        }
        if (! Schema::hasColumn('bookings', 'is_frontend')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->integer('is_frontend')->default(0);
            });
        }
        if (! Schema::hasColumn('bookings', 'payment_method')) {
            Schema::table('bookings', function (Blueprint $table) {
                $table->string('payment_method')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bookings', function (Blueprint $table) {
            //
        });
    }
}
