<?php

use App\Services\Restaurant\RestaurantDepartment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddLaneToRestaurantOrderItems extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('restaurant_order_items')) {
            return;
        }
        if (! Schema::hasColumn('restaurant_order_items', 'lane')) {
            Schema::table('restaurant_order_items', function (Blueprint $table) {
                $table->string('lane', 20)->nullable()->after('department');
                $table->index(['lane', 'status']);
            });
        }

        $rows = DB::table('restaurant_order_items as i')
            ->join('restaurant_orders as o', 'o.id', '=', 'i.restaurant_order_id')
            ->leftJoin('products as p', 'p.id', '=', 'i.product_id')
            ->whereNull('i.lane')
            ->get(['i.id', 'i.department', 'o.source', 'p.category_id']);

        foreach ($rows as $row) {
            $lane = RestaurantDepartment::laneForCategoryId($row->category_id);
            if (! $lane) {
                $lane = $row->department === 'food' ? 'restaurant' : ($row->department === 'drinks' ? 'bar' : null);
            }
            if ($lane && $row->source === 'frontend') {
                $lane = 'online';
            }
            if ($lane) {
                DB::table('restaurant_order_items')->where('id', $row->id)->update(['lane' => $lane]);
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('restaurant_order_items') && Schema::hasColumn('restaurant_order_items', 'lane')) {
            Schema::table('restaurant_order_items', function (Blueprint $table) {
                $table->dropIndex(['lane', 'status']);
                $table->dropColumn('lane');
            });
        }
    }
}
