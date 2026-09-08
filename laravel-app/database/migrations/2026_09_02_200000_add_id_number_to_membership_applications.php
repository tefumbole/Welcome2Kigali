<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdNumberToMembershipApplications extends Migration
{
    public function up()
    {
        if (Schema::hasTable('membership_applications') && ! Schema::hasColumn('membership_applications', 'id_number')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                $table->string('id_number', 64)->nullable()->after('id_type');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('membership_applications') && Schema::hasColumn('membership_applications', 'id_number')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                $table->dropColumn('id_number');
            });
        }
    }
}
