<?php

namespace App;

use App\Support\SchemaColumns;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable =[

        "purchase_id", "user_id", "sale_id", "cash_register_id", "account_id", "payment_reference", "amount", "used_points", "change", "paying_method", "payment_note", "debit_booking_id"
    ];

    public function save(array $options = [])
    {
        $this->attributes = SchemaColumns::forTable($this->getTable(), $this->attributes);

        return parent::save($options);
    }

    public function accounts() {
        return $this->belongsTo('App\Account', 'account_id');
    }
}
