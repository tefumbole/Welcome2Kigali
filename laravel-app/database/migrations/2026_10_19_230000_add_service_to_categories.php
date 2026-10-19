<?php

use App\Support\ServiceCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddServiceToCategories extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('categories')) {
            return;
        }
        if (! Schema::hasColumn('categories', 'service')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->string('service', 30)->nullable()->index();
            });
        }

        foreach (DB::table('categories')->whereNull('service')->get(['id', 'name']) as $category) {
            $service = ServiceCatalog::guessService($category->name);
            if ($service) {
                DB::table('categories')->where('id', $category->id)->update(['service' => $service]);
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('categories') && Schema::hasColumn('categories', 'service')) {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropColumn('service');
            });
        }
    }
}
