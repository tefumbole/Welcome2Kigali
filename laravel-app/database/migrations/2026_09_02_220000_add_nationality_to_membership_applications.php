<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddNationalityToMembershipApplications extends Migration
{
    public function up()
    {
        if (Schema::hasTable('membership_applications') && ! Schema::hasColumn('membership_applications', 'nationality')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                if (Schema::hasColumn('membership_applications', 'id_expires_on')) {
                    $table->string('nationality', 80)->nullable()->after('id_expires_on');
                } else {
                    $table->string('nationality', 80)->nullable();
                }
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('membership_applications') && Schema::hasColumn('membership_applications', 'nationality')) {
            Schema::table('membership_applications', function (Blueprint $table) {
                $table->dropColumn('nationality');
            });
        }
    }
}
