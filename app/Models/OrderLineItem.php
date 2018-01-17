<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderLineItem extends Model
{

    protected $casts = ['amount' => 'float'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
