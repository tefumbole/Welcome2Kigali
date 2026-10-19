<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMomoPaymentsTable extends Migration
{
    public function up()
    {
        if (Schema::hasTable('momo_payments')) {
            return;
        }
        Schema::create('momo_payments', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('purpose', 30);
            $table->unsignedBigInteger('ref_id');
            $table->integer('amount')->default(0);
            $table->string('reference')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['purpose', 'ref_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('momo_payments');
    }
}
