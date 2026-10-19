<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdIssuedOnToMembershipApplications extends Migration
{
    public function up()
    {
        if (Schema::hasTable('membership_applications') && ! Schema::hasColumn('membership_applications', 'id_issued_on')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                if (Schema::hasColumn('membership_applications', 'id_expires_on')) {
                    $table->date('id_issued_on')->nullable()->after('id_expires_on');
                } else {
                    $table->date('id_issued_on')->nullable();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('membership_applications') && Schema::hasColumn('membership_applications', 'id_issued_on')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                $table->dropColumn('id_issued_on');
            });
        }
    }
}
