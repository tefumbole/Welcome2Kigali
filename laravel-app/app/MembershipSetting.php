<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipSetting extends Model
{
    protected $fillable = ['key', 'value'];

    public static function get($key, $default = null)
    {
        $row = static::where('key', $key)->first();

        return $row ? $row->value : $default;
    }

    public static function put($key, $value)
    {
        return static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }
}
