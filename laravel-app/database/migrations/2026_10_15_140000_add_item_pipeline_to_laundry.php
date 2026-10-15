<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddItemPipelineToLaundry extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('laundry_order_items')) {
            return;
        }
        Schema::table('laundry_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('laundry_order_items', 'status')) {
                $table->string('status', 32)->default('received')->index()->after('note');
            }
            if (! Schema::hasColumn('laundry_order_items', 'accepted_at')) {
                $table->dateTime('accepted_at')->nullable()->after('collect_at');
            }
            if (! Schema::hasColumn('laundry_order_items', 'accepted_by')) {
                $table->unsignedInteger('accepted_by')->nullable()->after('accepted_at');
            }
            if (! Schema::hasColumn('laundry_order_items', 'cleaned_at')) {
                $table->dateTime('cleaned_at')->nullable()->after('accepted_by');
            }
            if (! Schema::hasColumn('laundry_order_items', 'cleaned_by')) {
                $table->unsignedInteger('cleaned_by')->nullable()->after('cleaned_at');
            }
        });

        if (! Schema::hasColumn('laundry_order_items', 'status')) {
            return;
        }

        $orders = DB::table('laundry_orders')->select('id', 'status', 'user_id', 'cleaning_started_at', 'ready_at')->get();
        foreach ($orders as $order) {
            $itemStatus = 'received';
            if ($order->status === 'collected') {
                $itemStatus = 'collected';
            } elseif (in_array($order->status, ['ready', 'awaiting_signature'], true)) {
                $itemStatus = 'ready';
            } elseif ($order->status === 'cleaning') {
                $itemStatus = 'cleaning';
            }
            $payload = ['status' => $itemStatus];
            if ($itemStatus !== 'received') {
                $payload['accepted_at'] = $order->cleaning_started_at ?: now();
                $payload['accepted_by'] = $order->user_id;
            }
            if (in_array($itemStatus, ['ready', 'collected'], true)) {
                $payload['cleaned_at'] = $order->ready_at ?: now();
                $payload['cleaned_by'] = $order->user_id;
            }
            DB::table('laundry_order_items')->where('laundry_order_id', $order->id)->update($payload);
        }
    }

    public function down()
    {
        if (! Schema::hasTable('laundry_order_items')) {
            return;
        }
        Schema::table('laundry_order_items', function (Blueprint $table) {
            foreach (['status', 'accepted_at', 'accepted_by', 'cleaned_at', 'cleaned_by'] as $col) {
                if (Schema::hasColumn('laundry_order_items', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
}
