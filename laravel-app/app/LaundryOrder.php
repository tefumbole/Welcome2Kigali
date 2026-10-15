<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LaundryOrder extends Model
{
    const STATUS_RECEIVED = 'received';
    const STATUS_CLEANING = 'cleaning';
    const STATUS_READY = 'ready';
    const STATUS_AWAITING_SIGNATURE = 'awaiting_signature';
    const STATUS_COLLECTED = 'collected';

    protected $fillable = [
        'reference_no',
        'sale_id',
        'customer_id',
        'user_id',
        'status',
        'notes',
        'cleaning_duration_hours',
        'cleaning_started_at',
        'ready_at',
        'collect_at',
        'qr_token',
        'collect_token',
        'collector_name',
        'signature_path',
        'signed_at',
        'collected_at',
        'collect_sent_at',
    ];

    protected $dates = [
        'cleaning_started_at',
        'ready_at',
        'collect_at',
        'signed_at',
        'collected_at',
        'collect_sent_at',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(LaundryOrderItem::class);
    }

    public function itemsInStatus($status)
    {
        return $this->items->filter(function ($item) use ($status) {
            $current = $item->status ?: LaundryOrderItem::STATUS_RECEIVED;

            return $current === $status;
        });
    }

    public function statusLabel()
    {
        $map = [
            self::STATUS_RECEIVED => 'Awaiting acceptance',
            self::STATUS_CLEANING => 'Awaiting cleaning',
            self::STATUS_READY => 'Ready for collection',
            self::STATUS_AWAITING_SIGNATURE => 'Awaiting signature',
            self::STATUS_COLLECTED => 'Collected',
        ];

        return $map[$this->status] ?? ucfirst(str_replace('_', ' ', (string) $this->status));
    }

    public function isPaid()
    {
        $sale = $this->sale;
        if (! $sale) {
            return false;
        }

        return (int) $sale->payment_status === 4;
    }

    public function dueAmount()
    {
        $sale = $this->sale;
        if (! $sale) {
            return 0;
        }

        return max(0, (float) $sale->grand_total - (float) $sale->paid_amount);
    }

    public function rotateCollectToken()
    {
        $this->collect_token = Str::random(48);
        $this->save();

        return $this->collect_token;
    }

    public function collectUrl()
    {
        if (empty($this->collect_token)) {
            return '';
        }

        return url('/laundry-collect/'.$this->collect_token);
    }

    public function scanUrl()
    {
        return url('/laundry/scan/'.$this->qr_token);
    }

    public function isOpenForSignature()
    {
        return $this->status === self::STATUS_AWAITING_SIGNATURE
            && ! empty($this->collect_token)
            && empty($this->signed_at);
    }
}
