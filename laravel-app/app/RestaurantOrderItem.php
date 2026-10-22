<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RestaurantOrderItem extends Model
{
    protected $fillable = [
        'restaurant_order_id', 'product_sale_id', 'product_id', 'product_name',
        'department', 'lane', 'qty', 'status', 'customer_comment', 'internal_note',
        'accepted_by', 'accepted_at', 'estimate_minutes', 'original_estimate_minutes',
        'previous_status',
        'expected_ready_at', 'ready_by', 'ready_at', 'delivered_by', 'delivered_at',
        'attempt_no', 'remake_of_id',
        'proposed_price', 'proposed_note', 'client_signature', 'client_signed_at',
    ];

    protected $dates = [
        'accepted_at', 'expected_ready_at', 'ready_at', 'delivered_at', 'client_signed_at',
    ];

    public function order()
    {
        return $this->belongsTo('App\RestaurantOrder', 'restaurant_order_id');
    }

    public function productSale()
    {
        return $this->belongsTo('App\Product_Sale', 'product_sale_id');
    }

    public function awaitingSignature()
    {
        if ($this->client_signed_at) {
            return false;
        }

        return $this->proposed_price !== null || trim((string) $this->proposed_note) !== '';
    }

    public function acceptedBy()
    {
        return $this->belongsTo('App\User', 'accepted_by');
    }

    public function readyBy()
    {
        return $this->belongsTo('App\User', 'ready_by');
    }

    public function deliveredBy()
    {
        return $this->belongsTo('App\User', 'delivered_by');
    }
}
