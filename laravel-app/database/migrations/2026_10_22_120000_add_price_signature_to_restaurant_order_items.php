<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriceSignatureToRestaurantOrderItems extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('restaurant_order_items')) {
            return;
        }
        if (! Schema::hasColumn('restaurant_order_items', 'proposed_price')) {
            Schema::table('restaurant_order_items', function (Blueprint $table) {
                $table->decimal('proposed_price', 12, 2)->nullable();
            });
        }
        if (! Schema::hasColumn('restaurant_order_items', 'proposed_note')) {
            Schema::table('restaurant_order_items', function (Blueprint $table) {
                $table->text('proposed_note')->nullable();
            });
        }
        if (! Schema::hasColumn('restaurant_order_items', 'client_signature')) {
            Schema::table('restaurant_order_items', function (Blueprint $table) {
                $table->longText('client_signature')->nullable();
            });
        }
        if (! Schema::hasColumn('restaurant_order_items', 'client_signed_at')) {
            Schema::table('restaurant_order_items', function (Blueprint $table) {
                $table->timestamp('client_signed_at')->nullable();
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('restaurant_order_items')) {
            return;
        }
        Schema::table('restaurant_order_items', function (Blueprint $table) {
            $drop = [];
            foreach (['client_signed_at', 'client_signature', 'proposed_note', 'proposed_price'] as $column) {
                if (Schema::hasColumn('restaurant_order_items', $column)) {
                    $drop[] = $column;
                }
            }
            if ($drop) {
                $table->dropColumn($drop);
            }
        });
    }
}
