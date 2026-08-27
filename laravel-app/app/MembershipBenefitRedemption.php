<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MembershipBenefitRedemption extends Model
{
    protected $fillable = [
        'membership_id', 'customer_id', 'product_id', 'sale_id', 'qty', 'value', 'redeemed_at',
    ];

    protected $dates = ['redeemed_at'];

    public function membership()
    {
        return $this->belongsTo(Membership::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
