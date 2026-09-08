<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnsureW2kCurrencyAndLetterhead extends Migration
{
    public function up()
    {
        if (Schema::hasTable('general_settings')) {
            if (! Schema::hasColumn('general_settings', 'email_header')) {
                Schema::table('general_settings', function (Blueprint $table) {
                    $table->string('email_header', 191)->nullable();
                });
            }
            if (! Schema::hasColumn('general_settings', 'email_footer')) {
                Schema::table('general_settings', function (Blueprint $table) {
                    $table->string('email_footer', 191)->nullable();
                });
            }
            if (! Schema::hasColumn('general_settings', 'email_water_mark')) {
                Schema::table('general_settings', function (Blueprint $table) {
                    $table->string('email_water_mark', 191)->nullable();
                });
            }
        }

        if (Schema::hasTable('currencies') && DB::table('currencies')->count() === 0) {
            DB::table('currencies')->insert([
                'id' => 1,
                'name' => 'Rwandan Franc',
                'code' => 'RWF',
                'exchange_rate' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        if (Schema::hasTable('general_settings') && Schema::hasTable('currencies')) {
            $currencyId = DB::table('currencies')->where('code', 'RWF')->value('id')
                ?: DB::table('currencies')->orderBy('id')->value('id');
            if ($currencyId) {
                $setting = DB::table('general_settings')->orderByDesc('id')->first();
                if ($setting && (empty($setting->currency) || ! DB::table('currencies')->where('id', $setting->currency)->exists())) {
                    DB::table('general_settings')->where('id', $setting->id)->update([
                        'currency' => $currencyId,
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down()
    {
    }
}
