<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class LaundryOrderItem extends Model
{
    const STATUS_RECEIVED = 'received';
    const STATUS_CLEANING = 'cleaning';
    const STATUS_READY = 'ready';
    const STATUS_COLLECTED = 'collected';

    protected $fillable = [
        'laundry_order_id',
        'product_id',
        'product_sale_id',
        'name',
        'qty',
        'unit_price',
        'total',
        'note',
        'collect_at',
        'status',
        'accepted_at',
        'accepted_by',
        'cleaned_at',
        'cleaned_by',
    ];

    protected $dates = [
        'collect_at',
        'accepted_at',
        'cleaned_at',
    ];

    public function order()
    {
        return $this->belongsTo(LaundryOrder::class, 'laundry_order_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cleanedBy()
    {
        return $this->belongsTo(User::class, 'cleaned_by');
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function statusLabel()
    {
        $map = [
            self::STATUS_RECEIVED => 'Awaiting acceptance',
            self::STATUS_CLEANING => 'Awaiting cleaning',
            self::STATUS_READY => 'Ready',
            self::STATUS_COLLECTED => 'Collected',
        ];

        return $map[$this->status] ?? ucfirst((string) $this->status);
    }

    public function stampName()
    {
        $user = $this->cleanedBy ?: $this->acceptedBy;
        if ($user && ! empty($user->name)) {
            return $user->name;
        }

        return null;
    }
}
