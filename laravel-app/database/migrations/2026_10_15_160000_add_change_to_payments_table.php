<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddChangeToPaymentsTable extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('payments')) {
            return;
        }
        if (! Schema::hasColumn('payments', 'change')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->double('change')->default(0)->after('amount');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'change')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('change');
            });
        }
    }
}
