<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureW2kModuleColumns extends Migration
{
    public function up()
    {
        if (Schema::hasTable('membership_applications')) {
            if (! Schema::hasColumn('membership_applications', 'beyond_user_id')) {
                Schema::table('membership_applications', function (Blueprint $table) {
                    $table->string('beyond_user_id', 64)->nullable();
                });
            } else {
                try {
                    DB::statement('ALTER TABLE membership_applications MODIFY beyond_user_id VARCHAR(64) NULL');
                } catch (\Throwable $e) {
                    // Column may already be a string on some environments.
                }
            }
        }

        if (Schema::hasTable('expenses') && ! Schema::hasColumn('expenses', 'category_id')) {
            Schema::table('expenses', function (Blueprint $table) {
                $table->unsignedInteger('category_id')->nullable()->after('expense_category_id');
            });
        }

        if (Schema::hasTable('orders')) {
            Schema::table('orders', function (Blueprint $table) {
                if (! Schema::hasColumn('orders', 'is_donation')) {
                    $table->tinyInteger('is_donation')->default(0);
                }
                if (! Schema::hasColumn('orders', 'is_service')) {
                    $table->tinyInteger('is_service')->default(0);
                }
            });
        }

        if (Schema::hasTable('asset_expenses')) {
            Schema::table('asset_expenses', function (Blueprint $table) {
                if (! Schema::hasColumn('asset_expenses', 'type')) {
                    $table->string('type', 40)->nullable()->index();
                }
                if (! Schema::hasColumn('asset_expenses', 'activity_type')) {
                    $table->string('activity_type', 80)->nullable()->index();
                }
            });
        }
    }

    public function down()
    {
        // Keep columns; they are required by the live modules.
    }
}
