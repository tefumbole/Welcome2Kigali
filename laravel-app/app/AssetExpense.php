<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AssetExpense extends Model
{
    protected $guarded = [];

    public function scopeOfKind($query, $kind)
    {
        if (! Schema::hasColumn($this->getTable(), 'type')) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('type', $kind);
    }

    public function scopeActivity($query, $type)
    {
        if (! Schema::hasColumn($this->getTable(), 'activity_type')) {
            return $query->whereRaw('0 = 1');
        }

        return $query->where('activity_type', $type);
    }
}
