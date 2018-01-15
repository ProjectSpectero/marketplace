<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    public function lineItems()
    {
        return $this->hasOne(OrderLineItems::class);
    }
}
