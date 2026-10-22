<?php

use App\Support\ServiceCatalog;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AssignMissingCategoryServices extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('categories') || ! Schema::hasColumn('categories', 'service')) {
            return;
        }

        $named = [
            'convenience store' => 'supermarket',
            'supermarket' => 'supermarket',
            'restaurant' => 'restaurant',
        ];
        foreach (DB::table('categories')->whereNull('service')->orWhere('service', '')->get(['id', 'name']) as $row) {
            $key = strtolower(trim((string) $row->name));
            $service = $named[$key] ?? ServiceCatalog::guessService($row->name);
            if ($service) {
                DB::table('categories')->where('id', $row->id)->update(['service' => $service]);
            }
        }

        // Children inherit the parent's service when they were left untagged.
        for ($pass = 0; $pass < 4; $pass++) {
            $parents = DB::table('categories')
                ->whereNotNull('service')
                ->where('service', '!=', '')
                ->get(['id', 'service']);
            foreach ($parents as $parent) {
                DB::table('categories')
                    ->where('parent_id', $parent->id)
                    ->where('id', '!=', $parent->id)
                    ->where(function ($query) {
                        $query->whereNull('service')->orWhere('service', '');
                    })
                    ->update(['service' => $parent->service]);
            }
        }
    }

    public function down()
    {
    }
}
