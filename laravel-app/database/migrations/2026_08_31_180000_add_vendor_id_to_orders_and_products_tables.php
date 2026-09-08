<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddVendorIdToOrdersAndProductsTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('orders') && ! Schema::hasColumn('orders', 'vendor_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedInteger('vendor_id')->nullable()->default(1)->after('user_id');
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'vendor_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('vendor_id')->nullable()->default(1);
            });
            DB::table('products')->whereNull('vendor_id')->update(['vendor_id' => 1]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('orders') && Schema::hasColumn('orders', 'vendor_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('vendor_id');
            });
        }
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'vendor_id')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('vendor_id');
            });
        }
    }
}
