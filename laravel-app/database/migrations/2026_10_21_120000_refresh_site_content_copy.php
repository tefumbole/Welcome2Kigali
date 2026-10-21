<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefreshSiteContentCopy extends Migration
{
    public function up()
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $replacements = [
            'content.services.hero_subtitle' => [
                'Comprehensive technology solutions tailored to your needs',
                'Everything you need in one place. Members get 10% off.',
            ],
            'content.services.subheading' => [
                "From IT infrastructure to cutting-edge AI solutions, we've got you covered.",
                'Everything you need in one place. Members get 10% off.',
            ],
            'content.services.heading' => [
                'Explore Our Expertise',
                'Our services',
            ],
            'content.menu.hero_subtitle' => [
                'Click an item to add it to your cart.',
                'Coffee, tea, juices, smoothies, and food. Tap an item to add it.',
            ],
        ];

        foreach ($replacements as $key => $pair) {
            $row = DB::table('site_settings')->where('key', $key)->first();
            if (! $row) {
                continue;
            }
            $value = json_decode($row->value, true);
            $current = json_last_error() === JSON_ERROR_NONE && is_string($value) ? $value : (string) $row->value;
            if (trim($current) !== $pair[0]) {
                continue;
            }
            DB::table('site_settings')->where('key', $key)->update([
                'value' => json_encode($pair[1]),
            ]);
        }
    }

    public function down()
    {
    }
}
