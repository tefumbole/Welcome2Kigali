<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddSignatureCountryPhoneToGuestReviews extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('guest_reviews')) {
            return;
        }

        Schema::table('guest_reviews', function (Blueprint $table) {
            if (! Schema::hasColumn('guest_reviews', 'country')) {
                $table->string('country', 80)->nullable()->after('guest_name');
            }
            if (! Schema::hasColumn('guest_reviews', 'phone')) {
                $table->string('phone', 32)->nullable()->after('country');
            }
            if (! Schema::hasColumn('guest_reviews', 'phone_verified_at')) {
                $table->timestamp('phone_verified_at')->nullable()->after('phone');
            }
            if (! Schema::hasColumn('guest_reviews', 'signature')) {
                $table->longText('signature')->nullable()->after('comment');
            }
        });
    }

    public function down()
    {
        if (! Schema::hasTable('guest_reviews')) {
            return;
        }

        Schema::table('guest_reviews', function (Blueprint $table) {
            foreach (['country', 'phone', 'phone_verified_at', 'signature'] as $column) {
                if (Schema::hasColumn('guest_reviews', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
