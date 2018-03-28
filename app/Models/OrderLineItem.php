<?php

namespace App;

class OrderLineItem extends BaseModel
{
    protected $casts = [ 'amount' => 'float', 'resource' => 'integer' ];
    protected $hidden = [ 'updated_at' ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
