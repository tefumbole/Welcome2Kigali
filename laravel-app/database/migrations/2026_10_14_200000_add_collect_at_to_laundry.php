<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCollectAtToLaundry extends Migration
{
    public function up()
    {
        if (Schema::hasTable('laundry_orders') && ! Schema::hasColumn('laundry_orders', 'collect_at')) {
            Schema::table('laundry_orders', function (Blueprint $table) {
                $table->dateTime('collect_at')->nullable()->after('ready_at');
            });
        }
        if (Schema::hasTable('laundry_order_items') && ! Schema::hasColumn('laundry_order_items', 'collect_at')) {
            Schema::table('laundry_order_items', function (Blueprint $table) {
                $table->dateTime('collect_at')->nullable()->after('note');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('laundry_orders') && Schema::hasColumn('laundry_orders', 'collect_at')) {
            Schema::table('laundry_orders', function (Blueprint $table) {
                $table->dropColumn('collect_at');
            });
        }
        if (Schema::hasTable('laundry_order_items') && Schema::hasColumn('laundry_order_items', 'collect_at')) {
            Schema::table('laundry_order_items', function (Blueprint $table) {
                $table->dropColumn('collect_at');
            });
        }
    }
}
