<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class MembershipPromotion extends Model
{
    protected $fillable = ['name', 'type', 'free_days', 'is_enabled', 'starts_at', 'ends_at', 'description'];

    protected $dates = ['starts_at', 'ends_at'];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function isLive()
    {
        if (! $this->is_enabled) {
            return false;
        }
        $now = Carbon::now();
        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }
        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public static function current()
    {
        return static::where('is_enabled', 1)->orderBy('id')->get()->first(function ($promo) {
            return $promo->isLive();
        });
    }
}
