<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LaundryOrderItem extends Model
{
    protected $fillable = [
        'laundry_order_id',
        'product_id',
        'product_sale_id',
        'name',
        'qty',
        'unit_price',
        'total',
        'note',
    ];

    public function order()
    {
        return $this->belongsTo(LaundryOrder::class, 'laundry_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
