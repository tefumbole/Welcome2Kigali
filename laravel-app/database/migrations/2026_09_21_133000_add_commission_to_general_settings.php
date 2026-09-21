<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCommissionToGeneralSettings extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }
        if (! Schema::hasColumn('general_settings', 'commission')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->integer('commission')->default(0)->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('general_settings') && Schema::hasColumn('general_settings', 'commission')) {
            Schema::table('general_settings', function (Blueprint $table) {
                $table->dropColumn('commission');
            });
        }
    }
}
