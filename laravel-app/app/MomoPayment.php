<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class MomoPayment extends Model
{
    protected $fillable = [
        'purpose', 'ref_id', 'amount', 'reference', 'status', 'paid_at',
    ];

    protected $dates = ['paid_at'];

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }
}
