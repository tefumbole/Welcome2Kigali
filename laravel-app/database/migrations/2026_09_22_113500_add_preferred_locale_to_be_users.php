<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreferredLocaleToBeUsers extends Migration
{
    public function up()
    {
        if (Schema::hasTable('be_users') && ! Schema::hasColumn('be_users', 'preferred_locale')) {
            Schema::table('be_users', function (Blueprint $table) {
                $table->string('preferred_locale', 8)->nullable();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('be_users') && Schema::hasColumn('be_users', 'preferred_locale')) {
            Schema::table('be_users', function (Blueprint $table) {
                $table->dropColumn('preferred_locale');
            });
        }
    }
}
