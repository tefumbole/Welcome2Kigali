<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreferredLocaleToVisitorRecords extends Migration
{
    public function up()
    {
        foreach (['customers', 'contact_messages', 'membership_applications', 'orders'] as $table) {
            if (Schema::hasTable($table) && ! Schema::hasColumn($table, 'preferred_locale')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->string('preferred_locale', 8)->nullable();
                });
            }
        }
    }

    public function down()
    {
        foreach (['customers', 'contact_messages', 'membership_applications', 'orders'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'preferred_locale')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropColumn('preferred_locale');
                });
            }
        }
    }
}
