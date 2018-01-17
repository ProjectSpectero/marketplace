<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function lineItems()
    {
        return $this->hasMany(OrderLineItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
