<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePawapayDepositsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('pawapay_deposits')) {
            return;
        }

        Schema::create('pawapay_deposits', function (Blueprint $table) {
            $table->increments('id');
            $table->string('deposit_id', 64)->unique();
            $table->string('purpose', 32);
            $table->unsignedInteger('purpose_id');
            $table->text('extra_ids')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 8)->default('RWF');
            $table->string('phone', 32)->nullable();
            $table->string('provider', 64)->nullable();
            $table->string('paying_method', 64)->nullable();
            $table->string('status', 24)->default('pending');
            $table->string('raw_status', 64)->nullable();
            $table->string('redirect_url', 500)->nullable();
            $table->timestamp('applied_at')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->timestamps();
            $table->index(['purpose', 'purpose_id']);
        });

        if (Schema::hasTable('membership_payments') && ! Schema::hasColumn('membership_payments', 'pawapay_deposit_id')) {
            Schema::table('membership_payments', function (Blueprint $table) {
                $table->string('pawapay_deposit_id', 64)->nullable()->after('campay_reference');
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('pawapay_deposits');
        if (Schema::hasTable('membership_payments') && Schema::hasColumn('membership_payments', 'pawapay_deposit_id')) {
            Schema::table('membership_payments', function (Blueprint $table) {
                $table->dropColumn('pawapay_deposit_id');
            });
        }
    }
}
