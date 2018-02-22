<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderLineItem extends BaseModel
{
    protected $casts = ['amount' => 'float', 'resource' => 'integer'];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
