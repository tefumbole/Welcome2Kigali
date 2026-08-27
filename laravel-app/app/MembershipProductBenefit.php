<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipProductBenefit extends Model
{
    protected $fillable = ['product_id', 'kind', 'member_price', 'qty', 'frequency', 'is_active'];

    protected $casts = [
        'member_price' => 'float',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
