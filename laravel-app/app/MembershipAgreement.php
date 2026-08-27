<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipAgreement extends Model
{
    protected $fillable = ['version', 'title', 'body', 'is_current', 'created_by'];

    protected $casts = ['is_current' => 'boolean'];

    public static function current()
    {
        return static::where('is_current', 1)->orderByDesc('id')->first();
    }
}
