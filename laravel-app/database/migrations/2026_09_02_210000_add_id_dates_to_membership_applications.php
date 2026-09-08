<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIdDatesToMembershipApplications extends Migration
{
    public function up()
    {
        if (Schema::hasTable('membership_applications') && ! Schema::hasColumn('membership_applications', 'date_of_birth')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                if (Schema::hasColumn('membership_applications', 'id_number')) {
                    $table->date('date_of_birth')->nullable()->after('id_number');
                } else {
                    $table->date('date_of_birth')->nullable();
                }
            });
        }
        if (Schema::hasTable('membership_applications') && ! Schema::hasColumn('membership_applications', 'id_expires_on')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                if (Schema::hasColumn('membership_applications', 'date_of_birth')) {
                    $table->date('id_expires_on')->nullable()->after('date_of_birth');
                } else {
                    $table->date('id_expires_on')->nullable();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('membership_applications') && Schema::hasColumn('membership_applications', 'id_expires_on')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                $table->dropColumn('id_expires_on');
            });
        }
        if (Schema::hasTable('membership_applications') && Schema::hasColumn('membership_applications', 'date_of_birth')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                $table->dropColumn('date_of_birth');
            });
        }
    }
}
