<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RebrandW2kCompanyNames extends Migration
{
    public function up()
    {
        if (Schema::hasTable('contract_settings')) {
            DB::table('contract_settings')
                ->where('key', 'company_legal_name')
                ->where('value', 'Beyond Enterprise')
                ->update(['value' => 'Welcome 2 Kigali Expats Club']);
            DB::table('contract_settings')
                ->where('key', 'default_jurisdiction')
                ->where('value', 'Republic of Cameroon')
                ->update(['value' => 'Republic of Rwanda']);
        }

        if (Schema::hasTable('wa_announcement_settings')) {
            DB::table('wa_announcement_settings')
                ->where('company_name', 'Beyond Enterprise')
                ->update(['company_name' => 'Welcome 2 Kigali Expats Club']);
            DB::table('wa_announcement_settings')
                ->where('default_header', 'Beyond Enterprise')
                ->update(['default_header' => 'Welcome 2 Kigali Expats Club']);
            DB::table('wa_announcement_settings')
                ->where('serial_prefix', 'BEY/ANN/')
                ->update(['serial_prefix' => 'W2K/ANN/']);
        }

        if (Schema::hasTable('general_settings')) {
            DB::table('general_settings')
                ->where('site_title', 'like', '%Beyond%')
                ->update(['site_title' => 'Welcome 2 Kigali Expats Club']);
        }
    }

    public function down()
    {
        // Intentionally empty: this app is Welcome 2 Kigali, not Beyond.
    }
}
