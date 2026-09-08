<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddIsDefaultDebitToAccountsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('accounts') || Schema::hasColumn('accounts', 'is_default_debit')) {
            return;
        }

        Schema::table('accounts', function (Blueprint $table) {
            $table->boolean('is_default_debit')->default(0)->after('is_default');
        });

        if (Schema::hasColumn('accounts', 'is_default')) {
            DB::table('accounts')->where('is_default', 1)->update(['is_default_debit' => 1]);
        }
    }

    public function down()
    {
        if (Schema::hasTable('accounts') && Schema::hasColumn('accounts', 'is_default_debit')) {
            Schema::table('accounts', function (Blueprint $table) {
                $table->dropColumn('is_default_debit');
            });
        }
    }
}
