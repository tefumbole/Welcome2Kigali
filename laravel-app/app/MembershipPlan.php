<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipPlan extends Model
{
    protected $fillable = ['code', 'name', 'duration_months', 'fee', 'is_active', 'sort_order'];

    protected $casts = [
        'fee' => 'float',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', 1)->orderBy('sort_order');
    }
}
