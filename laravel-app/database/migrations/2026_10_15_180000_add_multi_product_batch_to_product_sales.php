<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultiProductBatchToProductSales extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('product_sales')) {
            return;
        }

        if (! Schema::hasColumn('product_sales', 'multi_product_batch_id')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->text('multi_product_batch_id')->nullable();
            });
        }
        if (! Schema::hasColumn('product_sales', 'multi_product_batch_qty')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->text('multi_product_batch_qty')->nullable();
            });
        }
    }

    public function down()
    {
        if (! Schema::hasTable('product_sales')) {
            return;
        }

        if (Schema::hasColumn('product_sales', 'multi_product_batch_id')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->dropColumn('multi_product_batch_id');
            });
        }
        if (Schema::hasColumn('product_sales', 'multi_product_batch_qty')) {
            Schema::table('product_sales', function (Blueprint $table) {
                $table->dropColumn('multi_product_batch_qty');
            });
        }
    }
}
