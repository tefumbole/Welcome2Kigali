<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHoldFieldsToOrderItems extends Migration
{
    public function up()
    {
        if (Schema::hasTable('restaurant_order_items') && ! Schema::hasColumn('restaurant_order_items', 'previous_status')) {
            Schema::table('restaurant_order_items', function (Blueprint $table) {
                $table->string('previous_status', 20)->nullable();
            });
        }

        if (Schema::hasTable('laundry_order_items') && Schema::hasColumn('laundry_order_items', 'status')
            && ! Schema::hasColumn('laundry_order_items', 'previous_status')) {
            Schema::table('laundry_order_items', function (Blueprint $table) {
                $table->string('previous_status', 20)->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('restaurant_order_items') && Schema::hasColumn('restaurant_order_items', 'previous_status')) {
            Schema::table('restaurant_order_items', function (Blueprint $table) {
                $table->dropColumn('previous_status');
            });
        }
        if (Schema::hasTable('laundry_order_items') && Schema::hasColumn('laundry_order_items', 'previous_status')) {
            Schema::table('laundry_order_items', function (Blueprint $table) {
                $table->dropColumn('previous_status');
            });
        }
    }
}
