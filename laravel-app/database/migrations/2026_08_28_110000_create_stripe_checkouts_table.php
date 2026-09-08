<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStripeCheckoutsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('stripe_checkouts')) {
            return;
        }

        Schema::create('stripe_checkouts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('session_id', 128)->unique();
            $table->string('purpose', 32);
            $table->unsignedInteger('purpose_id');
            $table->text('extra_ids')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 8)->default('rwf');
            $table->string('paying_method', 64)->default('Visa');
            $table->string('status', 24)->default('pending');
            $table->string('redirect_url', 500)->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->index(['purpose', 'purpose_id']);
        });

        if (Schema::hasTable('membership_payments') && ! Schema::hasColumn('membership_payments', 'stripe_session_id')) {
            Schema::table('membership_payments', function (Blueprint $table) {
                $table->string('stripe_session_id', 128)->nullable();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('stripe_checkouts');
        if (Schema::hasTable('membership_payments') && Schema::hasColumn('membership_payments', 'stripe_session_id')) {
            Schema::table('membership_payments', function (Blueprint $table) {
                $table->dropColumn('stripe_session_id');
            });
        }
    }
}
