<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddRememberTokenToAuthTables extends Migration
{
    public function up()
    {
        if (Schema::hasTable('be_users') && ! Schema::hasColumn('be_users', 'remember_token')) {
            Schema::table('be_users', function (Blueprint $table) {
                $table->rememberToken();
            });
        }

        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->rememberToken();
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('be_users') && Schema::hasColumn('be_users', 'remember_token')) {
            Schema::table('be_users', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'remember_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('remember_token');
            });
        }
    }
}
